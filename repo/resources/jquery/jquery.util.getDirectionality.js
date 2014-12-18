( function ( $ ) {
'use strict';

/**
 * Returns the directionality of a language by querying the Universal Language Selector. If ULS is
 * not available the HTML element's `dir` attribute is evaluated. If that is unset, `auto` is
 * returned.
 * @method jQuery.util.getDirectionality
 * @member jQuery.util
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @param {string} languageCode
 * @return {string}
 */
$.util.getDirectionality = function( languageCode ) {
	var dir = $.uls && $.uls.data ? $.uls.data.getDir( languageCode ) : $( 'html' ).prop( 'dir' );
	return dir ? dir : 'auto';
};

}( jQuery ) );
