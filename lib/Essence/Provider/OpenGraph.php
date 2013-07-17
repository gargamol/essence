<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@author Laughingwithu <laughingwithu@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Provider;

use Essence\Exception;
use Essence\Media;
use Essence\Provider;
use Essence\Dom\Parser as DomParser;
use Essence\Http\Client as HttpClient;
use Essence\Utility\Hash;



/**
 *	Base class for an OpenGraph provider.
 *	This kind of provider extracts embed informations from OpenGraph meta tags.
 *
 *	@package fg.Essence.Provider
 */

class OpenGraph extends Provider {

	/**
	 *	Internal HTTP client.
	 *
	 *	@var Essence\Http\Client
	 */

	protected $_Http = null;



	/**
	 *	Internal DOM parser.
	 *
	 *	@var Essence\Dom\Parser
	 */

	protected $_Dom = null;



	/**
	 *	### Options
	 *
	 *	- 'html' callable( array $og ) A function to build an HTML code from
	 *		the given OpenGraph properties.
	 */

	protected $_properties = array(
		'prepare' => 'OpenGraph::prepare',
		'html' => 'OpenGraph::html'
	);



	/**
	 *	Constructor.
	 *
	 *	@param Essence\Http\Client $Http Http client.
	 *	@param Essence\Dom\Parser $Cache Dom parser.
	 */

	public function __construct( HttpClient $Http, DomParser $Dom ) {

		$this->_Http = $Http;
		$this->_Dom = $Dom;
	}



	/**
	 *	{@inheritDoc}
	 */

	protected function _embed( $url, $options ) {

		$og = $this->_extractInformations( $url );

		if ( empty( $og )) {
			throw new Exception(
				'Unable to extract OpenGraph data.'
			);
		}

		return new Media(
			Hash::reindex(
				$og,
				array(
					'og:type' => 'type',
					'og:title' => 'title',
					'og:description' => 'description',
					'og:site_name' => 'providerName',
					'og:image' => 'thumbnailUrl',
					'og:image:url' => 'thumbnailUrl',
					'og:image:width' => 'width',
					'og:image:height' => 'height',
					'og:video:width' => 'width',
					'og:video:height' => 'height',
					'og:url' => 'url'
				)
			)
		);
	}



	/**
	 *	Extracts OpenGraph informations from the given URL.
	 *
	 *	@param string $url URL to fetch informations from.
	 *	@return array Extracted informations.
	 */

	protected function _extractInformations( $url ) {

		$attributes = $this->_Dom->extractAttributes(
			$this->_Http->get( $url ),
			array(
				'meta' => array(
					'property' => '#^og:.+#i',
					'content'
				)
			)
		);

		$og = array( );

		if ( !empty( $attributes['meta'])) {
			foreach ( $attributes['meta'] as $meta ) {
				if ( !isset( $og[ $meta['property']])) {
					$og[ $meta['property']] = trim( $meta['content']);
				}
			}

			if ( empty( $og['html']) && is_callable( $this->_html )) {
				if ( empty( $og['og:url'])) {
					$og['og:url'] = $url;
				}

				$og['html'] = call_user_func( $this->_html, $og );
			}
		}

		return $og;
	}



	/**
	 *	Builds an HTML code from OpenGraph properties.
	 *
	 *	@param array $og OpenGraph properties.
	 *	@return string Generated HTML.
	 */

	public static function html( array $og ) {

		$title = isset( $og['og:title'])
			? $og['og:title']
			: $og['og:url'];

		$html = '';

		if ( isset( $og['og:video'])) {
			$html = sprintf(
				'<iframe src="%s" alt="%s" width="%d" height="%d" frameborder="0" allowfullscreen mozallowfullscreen webkitallowfullscreen></iframe>',
				$og['og:video'],
				$title,
				isset( $og['og:video:width'])
					? $og['og:video:width']
					: 560,
				isset( $og['og:video:height'])
					? $og['og:video:height']
					: 315
			);
		} else {
			$html = sprintf(
				'<a href="%s" alt="%s">%s</a>',
				$og['og:url'],
				isset( $og['og:description'])
					? $og['og:description']
					: $title,
				$title
			);
		}

		return $html;
	}
}

