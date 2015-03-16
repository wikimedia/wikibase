/**
 * @licence GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store fetching entities from API.
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 * @since 0.5
	 *
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {wikibase.store.FetchedContentUnserializer} fetchedEntityUnserializer;
	 * @param {string[]} languages
	 */
	var SELF = MODULE.ApiEntityStore = util.inherit(
		'WbApiEntityStore',
		MODULE.EntityStore,
		function( repoApi, fetchedEntityUnserializer, languages ) {
			this._fetchedEntityUnserializer = fetchedEntityUnserializer;
			this._languages = languages;
			this._repoApi = repoApi;
		},
	{

		/**
		 * @type {wikibase.store.FetchedContentUnserializer}
		 */
		_fetchedEntityUnserializer: null,

		/**
		 * @type {string[]}
		 */
		_languages: null,

		/**
		 * @type {wikibase.api.RepoApi}
		 */
		_repoApi: null,

		/**
		 * @see wikibase.store.EntityStore.getMultipleRaw
		 */
		getMultipleRaw: function( entityIds ) {
			var deferreds = $.map( entityIds, function() { return $.Deferred(); } ),
				self = this,
				entityIdToIndex = {};

			$.each( entityIds, function( i, entityId ) {
				entityIdToIndex[ entityId ] = i;
			} );

			this._repoApi.getEntities( entityIds, null, this._languages )
			.done( function( result ) {
				$.each( result.entities, function( id, entityData ) {
					if( entityData.missing === '' ) {
						return; // missing entity
					}

					var entity = self._fetchedEntityUnserializer.deserialize( {
						title: entityData.title,
						content: entityData
					} );
					deferreds[ entityIdToIndex[ entity.getContent().getId() ] ].resolve( entity );
				} );
			} )
			// FIXME: Evaluate failing promise
			.fail( function() {
				$.each( entityIds, function( i, entityId ) {
					deferreds[ entityIdToIndex[ entityId ] ].reject();
				} );
			} );

			return $.map( deferreds, function( deferred ) {
				return deferred.promise();
			} );
		}
	} );
}( wikibase, jQuery ) );
