<?php

/**
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

		'wikibase.view.ViewFactory.tests' => $moduleBase + array(
			'scripts' => array(
				'ViewFactory.tests.js',
			),
			'dependencies' => array(
				'wikibase.view.ViewFactory',
			),
		),

	);

	return $modules;
} );
