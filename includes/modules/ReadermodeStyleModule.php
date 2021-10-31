<?php

namespace GloopTweaks\ResourceLoaderModules;

use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\SlotRecord;
use ResourceLoaderSiteStylesModule;
use ResourceLoaderContext;

class ReadermodeStyleModule extends ResourceLoaderSiteStylesModule {
	// Readermode only makes sense for desktop.
	protected $targets = [ 'desktop' ];

	/**
	 * @param string $titleText
	 * @param ResourceLoaderContext $context
	 * @return null|string
	 * @since 1.32 added the $context parameter
	 */
	protected function getContent( $titleText, ResourceLoaderContext $context ) {
		global $wglCentralDB;
		$services = MediaWikiServices::getInstance();

		$title = $services->getTitleParser()->parseTitle( $titleText );
		$store = $services->getRevisionStoreFactory()->getRevisionStore( $wglCentralDB );
		$rev = $store->getRevisionByTitle( $title );

		$content = $rev ? $rev->getContent( SlotRecord::MAIN ) : null;
		if ( !$content ) {
			return null; // No content found
		}

		$handler = $content->getContentHandler();
		if ( $handler->isSupportedFormat( CONTENT_FORMAT_CSS ) ) {
			$format = CONTENT_FORMAT_CSS;
		} elseif ( $handler->isSupportedFormat( CONTENT_FORMAT_JAVASCRIPT ) ) {
			$format = CONTENT_FORMAT_JAVASCRIPT;
		} else {
			return null; // Bad content model
		}

		return $content->serialize( $format );
	}

	// Override getDB() to use metawiki rather than having a per-wiki MediaWiki:Vector-readermode.css.
	protected function getDB() {
		global $wglCentralDB;
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $lbFactory->getMainLB( $wglCentralDB );
		return $lb->getLazyConnectionRef( DB_REPLICA, [], $wglCentralDB );
	}

	/**
	 * Get list of pages used by this module
	 *
	 * @param ResourceLoaderContext $context
	 * @return array[]
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		$pages = [];
		if ( $this->getConfig()->get( 'UseSiteCss' ) ) {
			$skin = $context->getSkin();
			$pages['MediaWiki:' . ucfirst( $skin ) . '-readermode.css'] = [ 'type' => 'style' ];
		}
		return $pages;
	}

	// 'site' should be used, but can't as this module needs to load after 'site.styles'.
	public function getGroup() {
		return 'user';
	}
}
