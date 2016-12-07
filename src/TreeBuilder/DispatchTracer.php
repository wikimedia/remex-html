<?php

namespace Wikimedia\RemexHtml\TreeBuilder;
use Wikimedia\RemexHtml\Tokenizer\TokenHandler;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

class DispatchTracer implements TokenHandler {
	private $input;
	private $dispatcher;
	private $callback;

	function __construct( $input, Dispatcher $dispatcher, $callback ) {
		$this->input = $input;
		$this->dispatcher = $dispatcher;
		$this->callback = $callback;
	}

	private function trace( $msg ) {
		call_user_func( $this->callback, "[Dispatch] $msg" );
	}

	private function excerpt( $text ) {
		if ( strlen( $text ) > 20 ) {
			$text = substr( $text, 0, 20 ) . '...';
		}
		return str_replace( "\n", "\\n", $text );
	}

	private function wrap( $funcName, $sourceStart, $sourceLength, $args ) {
		$prevHandler = $this->getHandlerName();
		$excerpt = $this->excerpt( substr( $this->input, $sourceStart, $sourceLength ) );
		$msg = "$funcName $prevHandler \"$excerpt\"";
		$this->trace( $msg );
		call_user_func_array( [ $this->dispatcher, $funcName ], $args );
		$handler = $this->getHandlerName();
		if ( $prevHandler !== $handler ) {
			$this->trace( "$prevHandler -> $handler" );
		}
	}

	private function getHandlerName() {
		$name = get_class( $this->dispatcher->getHandler() );
		$slashPos = strrpos( $name, '\\' );
		if ( $slashPos === false ) {
			return $name;
		} else {
			return substr( $name, $slashPos + 1 );
		}
	}

	public function startDocument( $ns, $name ) {
		$prevHandler = $this->getHandlerName();
		$nsMsg = $ns === null ? 'NULL' : $ns;
		$nameMsg = $name === null ? 'NULL' : $name;
		$this->trace( "startDocument $prevHandler $nsMsg $nameMsg" );
		$this->dispatcher->startDocument( $ns, $name );
		$handler = $this->getHandlerName();
		if ( $prevHandler !== $handler ) {
			$this->trace( "$prevHandler -> $handler" );
		}
	}

	public function endDocument( $pos ) {
		$this->wrap( __FUNCTION__, $pos, 0, func_get_args() );
	}

	public function error( $text, $pos ) {
		$handler = $this->getHandlerName();
		$this->trace( "error $handler \"$text\"" );
		$this->dispatcher->error( $text, $pos );
	}

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->wrap( __FUNCTION__, $sourceStart, $sourceLength, func_get_args() );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$this->wrap( __FUNCTION__, $sourceStart, $sourceLength, func_get_args() );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$this->wrap( __FUNCTION__, $sourceStart, $sourceLength, func_get_args() );
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->wrap( __FUNCTION__, $sourceStart, $sourceLength, func_get_args() );
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->wrap( __FUNCTION__, $sourceStart, $sourceLength, func_get_args() );
	}
}
