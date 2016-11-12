<?php

namespace Wikimedia\RemexHtml;

/**
 * Generate HTMLData.php. This can be executed e.g. with
 *
 * echo 'Wikimedia\RemexHtml\GenerateDataFiles::run()' | hhvm bin/test.php
 */
class GenerateDataFiles {
	/**
	 * The only public entry point
	 */
	public static function run() {
		$instance = new self;
		$instance->execute();
	}

	/**
	 * This is the character entity mapping table copied from 
	 * https://www.w3.org/TR/2014/REC-html5-20141028/syntax.html#tokenizing-character-references
	 */
	private static $legacyNumericEntityData = <<<EOT
0x00 	U+FFFD 	REPLACEMENT CHARACTER
0x80 	U+20AC 	EURO SIGN (€)
0x82 	U+201A 	SINGLE LOW-9 QUOTATION MARK (‚)
0x83 	U+0192 	LATIN SMALL LETTER F WITH HOOK (ƒ)
0x84 	U+201E 	DOUBLE LOW-9 QUOTATION MARK („)
0x85 	U+2026 	HORIZONTAL ELLIPSIS (…)
0x86 	U+2020 	DAGGER (†)
0x87 	U+2021 	DOUBLE DAGGER (‡)
0x88 	U+02C6 	MODIFIER LETTER CIRCUMFLEX ACCENT (ˆ)
0x89 	U+2030 	PER MILLE SIGN (‰)
0x8A 	U+0160 	LATIN CAPITAL LETTER S WITH CARON (Š)
0x8B 	U+2039 	SINGLE LEFT-POINTING ANGLE QUOTATION MARK (‹)
0x8C 	U+0152 	LATIN CAPITAL LIGATURE OE (Œ)
0x8E 	U+017D 	LATIN CAPITAL LETTER Z WITH CARON (Ž)
0x91 	U+2018 	LEFT SINGLE QUOTATION MARK (‘)
0x92 	U+2019 	RIGHT SINGLE QUOTATION MARK (’)
0x93 	U+201C 	LEFT DOUBLE QUOTATION MARK (“)
0x94 	U+201D 	RIGHT DOUBLE QUOTATION MARK (”)
0x95 	U+2022 	BULLET (•)
0x96 	U+2013 	EN DASH (–)
0x97 	U+2014 	EM DASH (—)
0x98 	U+02DC 	SMALL TILDE (˜)
0x99 	U+2122 	TRADE MARK SIGN (™)
0x9A 	U+0161 	LATIN SMALL LETTER S WITH CARON (š)
0x9B 	U+203A 	SINGLE RIGHT-POINTING ANGLE QUOTATION MARK (›)
0x9C 	U+0153 	LATIN SMALL LIGATURE OE (œ)
0x9E 	U+017E 	LATIN SMALL LETTER Z WITH CARON (ž)
0x9F 	U+0178 	LATIN CAPITAL LETTER Y WITH DIAERESIS (Ÿ)
EOT;

	/**
	 * This is the list of public identifier prefixes that cause quirks mode
	 * to be set, from § 8.2.5.4.1
	 */
	private static $quirkyPublicPrefixes = [
		"+//Silmaril//dtd html Pro v0r11 19970101//",
		"-//AdvaSoft Ltd//DTD HTML 3.0 asWedit + extensions//",
		"-//AS//DTD HTML 3.0 asWedit + extensions//",
		"-//IETF//DTD HTML 2.0 Level 1//",
		"-//IETF//DTD HTML 2.0 Level 2//",
		"-//IETF//DTD HTML 2.0 Strict Level 1//",
		"-//IETF//DTD HTML 2.0 Strict Level 2//",
		"-//IETF//DTD HTML 2.0 Strict//",
		"-//IETF//DTD HTML 2.0//",
		"-//IETF//DTD HTML 2.1E//",
		"-//IETF//DTD HTML 3.0//",
		"-//IETF//DTD HTML 3.2 Final//",
		"-//IETF//DTD HTML 3.2//",
		"-//IETF//DTD HTML 3//",
		"-//IETF//DTD HTML Level 0//",
		"-//IETF//DTD HTML Level 1//",
		"-//IETF//DTD HTML Level 2//",
		"-//IETF//DTD HTML Level 3//",
		"-//IETF//DTD HTML Strict Level 0//",
		"-//IETF//DTD HTML Strict Level 1//",
		"-//IETF//DTD HTML Strict Level 2//",
		"-//IETF//DTD HTML Strict Level 3//",
		"-//IETF//DTD HTML Strict//",
		"-//IETF//DTD HTML//",
		"-//Metrius//DTD Metrius Presentational//",
		"-//Microsoft//DTD Internet Explorer 2.0 HTML Strict//",
		"-//Microsoft//DTD Internet Explorer 2.0 HTML//",
		"-//Microsoft//DTD Internet Explorer 2.0 Tables//",
		"-//Microsoft//DTD Internet Explorer 3.0 HTML Strict//",
		"-//Microsoft//DTD Internet Explorer 3.0 HTML//",
		"-//Microsoft//DTD Internet Explorer 3.0 Tables//",
		"-//Netscape Comm. Corp.//DTD HTML//",
		"-//Netscape Comm. Corp.//DTD Strict HTML//",
		"-//O'Reilly and Associates//DTD HTML 2.0//",
		"-//O'Reilly and Associates//DTD HTML Extended 1.0//",
		"-//O'Reilly and Associates//DTD HTML Extended Relaxed 1.0//",
		"-//SoftQuad Software//DTD HoTMetaL PRO 6.0::19990601::extensions to HTML 4.0//",
		"-//SoftQuad//DTD HoTMetaL PRO 4.0::19971010::extensions to HTML 4.0//",
		"-//Spyglass//DTD HTML 2.0 Extended//",
		"-//SQ//DTD HTML 2.0 HoTMetaL + extensions//",
		"-//Sun Microsystems Corp.//DTD HotJava HTML//",
		"-//Sun Microsystems Corp.//DTD HotJava Strict HTML//",
		"-//W3C//DTD HTML 3 1995-03-24//",
		"-//W3C//DTD HTML 3.2 Draft//",
		"-//W3C//DTD HTML 3.2 Final//",
		"-//W3C//DTD HTML 3.2//",
		"-//W3C//DTD HTML 3.2S Draft//",
		"-//W3C//DTD HTML 4.0 Frameset//",
		"-//W3C//DTD HTML 4.0 Transitional//",
		"-//W3C//DTD HTML Experimental 19960712//",
		"-//W3C//DTD HTML Experimental 970421//",
		"-//W3C//DTD W3 HTML//",
		"-//W3O//DTD W3 HTML 3.0//",
		"-//WebTechs//DTD Mozilla HTML 2.0//",
		"-//WebTechs//DTD Mozilla HTML//",
	];

	private static $special = [
		self::NS_HTML => 'address, applet, area, article, aside, base, basefont,
			bgsound, blockquote, body, br, button, caption, center, col, colgroup,
			dd, details, dir, div, dl, dt, embed, fieldset, figcaption, figure,
			footer, form, frame, frameset, h1, h2, h3, h4, h5, h6, head, header,
			hgroup, hr, html, iframe, img, input, isindex, li, link, listing,
			main, marquee, meta, nav, noembed, noframes, noscript, object, ol,
			p, param, plaintext, pre, script, section, select, source, style,
			summary, table, tbody, td, template, textarea, tfoot, th, thead,
			title, tr, track, ul, wbr, xmp',
		self::NS_MATHML => 'mi, mo, mn, ms, mtext, annotation-xml',
		self::NS_SVG => 'foreignObject, desc, title',
	];

	private function makeRegexAlternation( $array ) {
		$regex = '';
		foreach ( $array as $value ) {
			if ( $regex !== '' ) {
				$regex .= '|';
			}
			$regex .= "\n\t\t" . preg_quote( substr( $value, 1 ), '~' );
		}
		return $regex;
	}

	private function execute() {
		$entitiesJson = file_get_contents( __DIR__ . '/entities.json' );

		if ( $entitiesJson === false ) {
			throw new \Exception( "Please download entities.json from " .
				"https://www.w3.org/TR/2014/REC-html5-20141028/entities.json" );
		}

		$entities = (array)json_decode( $entitiesJson );

		$entityTranslations = [];
		foreach ( $entities as $entity => $info ) {
			$entityTranslations[substr( $entity, 1 )] = $info->characters;
		}

		// Sort descending by length
		uksort( $entities, function ( $a, $b ) {
			if ( strlen( $a ) > strlen( $b ) ) {
				return -1;
			} elseif ( strlen( $a ) < strlen( $b ) ) {
				return 1;
			} else {
				return strcmp( $a, $b );
			}
		} );

		$entityRegex = $this->makeRegexAlternation( array_keys( $entities ) );

		$matches = [];
		preg_match_all( '/^0x([0-9A-F]+)\s+U\+([0-9A-F]+)/m',
			self::$legacyNumericEntityData, $matches, PREG_SET_ORDER );

		$legacyNumericEntities = [];
		foreach ( $matches as $match ) {
			$legacyNumericEntities[ intval( $match[1], 16 ) ] =
				\UtfNormal\Utils::codepointToUtf8( intval( $match[2], 16  ) );
		}

		$quirkyRegex = 
			'~' .
			$this->makeRegexAlternation( self::$quirkyPublicPrefixes ) .
			'~xAi';

		$encEntityRegex = var_export( $entityRegex, true );
		$encTranslations = var_export( $entityTranslations, true );
		$encLegacy = var_export( $legacyNumericEntities, true );
		$encQuirkyRegex = var_export( $quirkyRegex, true );

		$special = [];
		foreach ( self::$special as $ns => $str ) {
			$special[$ns] = array_map( 'trim', explode( ',', $str ) );
		}
		$encSpecial = var_export( $special, true );

		$fileContents = '<' . <<<PHP
?php

/**
 * This data file is machine generated, see GenerateDataFiles.php
 */

namespace Wikimedia\RemexHtml;

class HTMLData {
	const NS_HTML = 'http://www.w3.org/1999/xhtml';
	const NS_MATHML = 'http://www.w3.org/1998/Math/MathML';
	const NS_SVG = 'http://www.w3.org/2000/svg';
	const NS_XLINK = 'http://www.w3.org/1999/xlink';
	const NS_XML = 'http://www.w3.org/XML/1998/namespace';
	const NS_XMLNS = 'http://www.w3.org/2000/xmlns/';

	static public \$special = $encSpecial;
	static public \$namedEntityRegex = $encEntityRegex;
	static public \$namedEntityTranslations = $encTranslations;
	static public \$legacyNumericEntities = $encLegacy;
	static public \$quirkyPrefixRegex = $encQuirkyRegex;
}
PHP;

		file_put_contents( __DIR__ . '/HTMLData.php', $fileContents );
	}
}
