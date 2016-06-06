<?php

namespace Wikimedia\RemexHtml\Balancer;
use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

class BeforeHtml extends InsertionMode {
	function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		// Ignore whitespace
		$wsLength = strspn( $text, "\t\n\f\r ", $start, $length );
		$length -= $wsLength;
		if ( !$length ) {
			return;
		}
		$start += $wsLength;
		// Generate missing <html> tag
		$this->balancer->startTag( 'html', new PlainAttributes, false, $sourceStart, 0 );
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HEAD )
			->characters( $text, $start, $length, $sourceStart, $sourceLength );
	}

	function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		if ( $name !== 'html' ) {
			$this->balancer->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
			$this->dispatcher->switchMode( Dispatcher::BEFORE_HEAD );
		} else {
			$this->balancer->startTag( 'html', new PlainAttributes,	false, $sourceStart, 0 );
			$this->dispatcher->switchMode( Dispatcher::BEFORE_HEAD )
				->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	function endTag( $name, $sourceStart, $sourceLength ) {
		$allowed = [ "head" => true, "body" => true, "html" => true, "br" => true ];
		if ( !isset( $allowed[$name] ) ) {
			$this->balancer->error( 'end tag not allowed before html', $sourceStart );
			return;
		}
		$this->balancer->startTag( 'html', new PlainAttributes, false, $sourceStart, 0 );
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HEAD )
			->endTag( $name, $sourceStart, $sourceLength );
	}
}
