<?php

namespace RemexHtml\TreeBuilder;
use RemexHtml\DOM;
use RemexHtml\HTMLData;
use RemexHtml\Tokenizer;
use RemexHtml\Serializer;

class TreeBuilderTest extends \PHPUnit_Framework_TestCase {
	private static $testDirs = [
		'html5lib/tree-construction',
		'local/tree-construction',
	];

	private static $fileBlacklist = [
		// Refers to a newer version of the HTML spec
		'tree-construction/menuitem-element.dat',
	];

	private static $testBlacklist = [
	];

	private static $domTestBlacklist = [
		// Invalid tag name
		'tree-construction/html5test-com.dat:1',
		'tree-construction/webkit01.dat:179',

		// Invalid attribute name
		'tree-construction/html5test-com.dat:12',
		'tree-construction/html5test-com.dat:39',
		'tree-construction/tests14.dat:45',
		'tree-construction/tests14.dat:55',
		'tree-construction/tests14.dat:67',
		'tree-construction/tests26.dat:263',
		'tree-construction/webkit01.dat:606',

		// Invalid doctype
		'tree-construction/doctype01.dat:32',
		'tree-construction/doctype01.dat:45',
		'tree-construction/tests6.dat:48',
	];

	public function serializerProvider() {
		return $this->provider( 'serializer' );
	}

	public function domProvider() {
		return $this->provider( 'dom' );
	}

	private function provider( $type ) {
		$testFiles = [];
		foreach ( self::$testDirs as $testDir ) {
			$testFiles = array_merge( $testFiles, glob( __DIR__ . "/../$testDir/*.dat" ) );
		}
		$args = [];
		foreach ( $testFiles as $fileName ) {
			if ( in_array( 'tree-construction/' . basename( $fileName ), self::$fileBlacklist ) ) {
				continue;
			}
			$tests = $this->readFile( $fileName, $type );

			foreach ( $tests as $test ) {
				if ( isset( $test['scripting'] ) ) {
					$args[] = [ $test ];
				} else {
					$test['scripting'] = false;
					$args[] = [ $test ];
					$test['scripting'] = true;
					$args[] = [ $test ];
				}
			}
		}
		return $args;
	}

	private function readFile( $fileName, $type ) {
		$text = file_get_contents( $fileName );
		if ( $text === false ) {
			throw new \Exception( "Cannot read test file: $fileName" );
		}
		$baseName = "tree-construction/" . basename( $fileName );
		$pos = 0;
		$lineNum = 1;
		$tests = [];
		while ( true ) {
			$startLine = $lineNum;
			$section = $this->readSection( $text, $pos, $lineNum );
			if ( !$section ) {
				break;
			}
			if ( $section['name'] !== 'data' ) {
				throw new \Exception( "Invalid section at start of test: ${section['name']}" );
			}

			$test = [
				'data' => $section['value'],
				'file' => $baseName,
				'line' => $startLine
			];

			do {
				$section = $this->readSection( $text, $pos, $lineNum );
				if ( !$section ) {
					break;
				}
				switch ( $section['name'] ) {
				case 'errors':
					$test['errors'] = explode( "\n", rtrim( $section['value'], "\n" ) );
					break;

				case 'document':
					$test['document'] = $section['value'];
					break;

				case 'document-fragment':
					$test['fragment'] = trim( $section['value'] );
					break;

				case 'script-on':
					$test['scripting'] = true;
					break;

				case 'script-off':
					$test['scripting'] = false;
					break;
				}
			} while ( !$section['end'] );

			if ( in_array( "$baseName:$startLine", self::$testBlacklist ) ) {
				continue;
			}
			if ( $type === 'dom'
				&& in_array( "$baseName:$startLine", self::$domTestBlacklist )
			) {
				continue;
			}

			$tests[] = $test;
		}
		return $tests;
	}

	private function readSection( $text, &$pos, &$lineNum ) {
		if ( !preg_match( '/#([a-z-]*)\n/A', $text, $m, 0, $pos ) ) {
			return false;
		}

		$sectionLineNum = $lineNum++;
		$startPos = $pos;
		$name = $m[1];
		$valuePos = $pos + strlen( $m[0] );
		$pos = $valuePos;
		$value = '';
		$isEnd = false;

		while ( !$isEnd && $pos < strlen( $text ) ) {
			$lineStart = $pos;
			$lineLength = strcspn( $text, "\n", $pos );
			$pos += $lineLength;
			if ( $pos >= strlen( $text ) ) {
				$isEnd = true;
			} elseif ( $text[$pos] === "\n" ) {
				$pos++;
				$lineNum++;
			}

			$line = substr( $text, $lineStart, $lineLength );
			if ( $name === 'data' ) {
				// Double line breaks can appear in #data
			} elseif ( $name === 'document' && preg_match( '/\s*"/A', $text, $m, 0, $pos ) ) {
				// Line breaks in #document can be escaped with quotes
			} elseif ( $line === '' ) {
				$isEnd = true;
				break;
			}

			if ( preg_match( '/^#([a-z-]*)$/', $line ) ) {
				$pos = $lineStart;
				$lineNum--;
				break;
			}
			if ( $value !== '' ) {
				$value .= "\n";
			}
			$value .= $line;
		}

		$result = [
			'name' => $name,
			'value' => $value,
			'line' => $sectionLineNum,
			'end' => $isEnd,
		];
		return $result;
	}

	/** @dataProvider serializerProvider */
	public function testSerializer( $params ) {
		$formatter = new Serializer\TestFormatter;
		$serializer = new Serializer\Serializer( $formatter );
		$this->runWithSerializer( $serializer, $params );
	}

	/** @dataProvider domProvider */
	public function testDOMSerializer( $params ) {
		$formatter = new Serializer\TestFormatter;
		$builder = new DOM\DOMBuilder;
		$serializer = new DOM\DOMSerializer( $builder, $formatter );
		$this->runWithSerializer( $serializer, $params );
	}

	private function runWithSerializer( Serializer\AbstractSerializer $serializer, $params ) {
		if ( !isset( $params['document'] ) ) {
			throw new \Exception( "Test lacks #document: {$params['file']}:{$params['line']}" );
		}
		$treeBuilder = new TreeBuilder( $serializer, [
			'scriptingFlag' => $params['scripting']
		] );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $params['data'], [] );

		$tokenizerOptions = [];

		if ( isset( $params['fragment'] ) ) {
			$fragment = explode( ' ', $params['fragment'] );
			if ( count( $fragment ) > 1 ) {
				if ( $fragment[0] === 'svg' ) {
					$ns = HTMLData::NS_SVG;
				} elseif ( $fragment[0] === 'math' ) {
					$ns = HTMLData::NS_MATHML;
				} else {
					$ns = HTMLData::NS_HTML;
				}
				$name = $fragment[1];
			} else {
				$ns = HTMLData::NS_HTML;
				$name = $fragment[0];
			}
			$tokenizerOptions['fragmentNamespace'] = $ns;
			$tokenizerOptions['fragmentName'] = $name;
		}

		$tokenizer->execute( $tokenizerOptions );
		$result = $serializer->getResult();

		// Normalize adjacent text nodes
		do {
			$prevResult = $result;
			$result = preg_replace( '/^([ ]*)"([^"]*+)"\n\1"([^"]*+)"\n/m', "\\1\"\\2\\3\"\n", $result );
		} while ( $prevResult !== $result );

		// Format appropriately
		$result = preg_replace( '/^/m', "| ", $result );
		$result = str_replace( '<EOL>', "\n", $result );

		// Normalize terminating line break
		$result = rtrim( $result, "\n" );
		$expected = rtrim( $params['document'], "\n" );

		$this->assertEquals( $expected, $result, "{$params['file']}:{$params['line']}" );
	}
}
