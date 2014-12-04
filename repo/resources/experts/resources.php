<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(

		'wikibase.experts.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'__namespace.js'
			),
			'dependencies' => array(
				'wikibase',
			)
		),

		'wikibase.experts.getStore' => $moduleTemplate + array(
			'scripts' => array(
				'getStore.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.CommonsMediaType',
				'jquery.valueview.experts.GlobeCoordinateInput',
				'jquery.valueview.experts.MonolingualText',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.TimeInput',
				'jquery.valueview.experts.UnsupportedValue',
				'wikibase.datamodel.EntityId',
				'wikibase.experts.__namespace',
				'wikibase.experts.Item',
			),
		),

		'wikibase.experts.Entity' => $moduleTemplate + array(
			'scripts' => array(
				'Entity.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.valueview.Expert',
				'jquery.valueview.experts.StringValue',
				'mw.config.values.wbRepo',
				'util.inherit',
				'wikibase.experts.__namespace',
			),
		),

		'wikibase.experts.Item' => $moduleTemplate + array(
			'scripts' => array(
				'Item.js',
			),
			'dependencies' => array(
				'jquery.valueview.Expert',
				'jquery.wikibase.entityselector',
				'wikibase.experts.__namespace',
				'wikibase.experts.Entity',
			),
		),
	);

} );
