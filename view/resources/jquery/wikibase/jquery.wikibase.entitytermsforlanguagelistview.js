/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Displays multiple fingerprints (see jQuery.wikibase.entitytermsforlanguageview).
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object[]} value
 *         Object representing the widget's value.
 *         Structure: [
 *           {
 *             language: <{string]>,
 *             label: <{wikibase.datamodel.Term}>,
 *             description: <{wikibase.datamodel.Term}>,
 *             aliases: <{wikibase.datamodel.MultiTerm}>
 *           }[, ...]
 *         ]
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - {jQuery.Event}
 *
 * @event stopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *        - {Function} Callback function.
 *
 * @event afterstopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event toggleerror
 *        - {jQuery.Event}
 *        - {Error|null}
 */
$.widget( 'wikibase.entitytermsforlanguagelistview', PARENT, {
	options: {
		template: 'wikibase-entitytermsforlanguagelistview',
		templateParams: [
			mw.msg( 'wikibase-entitytermsforlanguagelistview-language' ),
			mw.msg( 'wikibase-entitytermsforlanguagelistview-label' ),
			mw.msg( 'wikibase-entitytermsforlanguagelistview-description' ),
			mw.msg( 'wikibase-entitytermsforlanguagelistview-aliases' ),
			'' // entitytermsforlanguageview
		],
		templateShortCuts: {
			$header: '.wikibase-entitytermsforlanguagelistview-header',
			$listview: '.wikibase-entitytermsforlanguagelistview-listview'
		},
		value: [],
		entityChangersFactory: null
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if ( !$.isArray( this.options.value )
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._verifyExistingDom();
		this._createListView();

		this.element.addClass( 'wikibase-entitytermsforlanguagelistview' );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		// When destroying a widget not initialized properly, shortcuts will not have been created.
		if ( this.$listview ) {
			// When destroying a widget not initialized properly, listview will not have been created.
			var listview = this.$listview.data( 'listview' );

			if ( listview ) {
				listview.destroy();
			}
		}

		this.element.removeClass( 'wikibase-entitytermsforlanguagelistview' );
		PARENT.prototype.destroy.call( this );
	},

	_verifyExistingDom: function() {
		var $entitytermsforlanguageview = this.element
			.find( '.wikibase-entitytermsforlanguageview' );

		if ( $entitytermsforlanguageview.length === 0 ) {
			// No need to verify an empty DOM
			return;
		}

		// Scrape languages from static HTML:
		var scrapedLanguages = [],
			i;
		$entitytermsforlanguageview.each( function() {
			$.each( $( this ).attr( 'class' ).split( ' ' ), function() {
				if ( this.indexOf( 'wikibase-entitytermsforlanguageview-' ) === 0 ) {
					scrapedLanguages.push(
						this.split( 'wikibase-entitytermsforlanguageview-' )[1]
					);
					return false;
				}
			} );
		} );

		var mismatch = scrapedLanguages.length !== this.options.value.length;

		if ( !mismatch ) {
			for ( i = 0; i < scrapedLanguages.length; i++ ) {
				if ( scrapedLanguages[i] !== this.options.value[i].language ) {
					mismatch = true;
					break;
				}
			}
		}

		if ( mismatch ) {
			mw.log.warn( 'Existing entitytermsforlanguagelistview DOM does not match configured languages' );
			$entitytermsforlanguageview.remove();
		}
	},

	/**
	 * Creates the listview widget managing the entitytermsforlanguageview widgets
	 */
	_createListView: function() {
		var self = this,
			listItemWidget = $.wikibase.entitytermsforlanguageview,
			prefix = listItemWidget.prototype.widgetEventPrefix;

		// Fully encapsulate child widgets by suppressing their events:
		this.element
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			event.stopPropagation();
			self.setError( error );
		} )
		.on(
			[
				prefix + 'create.' + this.widgetName,
				prefix + 'afterstartediting.' + this.widgetName,
				prefix + 'stopediting.' + this.widgetName,
				prefix + 'afterstopediting.' + this.widgetName,
				prefix + 'disable.' + this.widgetName
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		);

		this.$listview
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: listItemWidget,
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						entityChangersFactory: self.options.entityChangersFactory,
						helpMessage: mw.msg(
							'wikibase-entitytermsforlanguageview-input-help-message',
							wb.getLanguageNameByCode( value.language )
						)
					};
				}
			} ),
			value: self.options.value || null,
			listItemNodeName: 'TR'
		} );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return !!this.$listview.data( 'listview' ).items().length;
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			isValid = true;

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			isValid = entitytermsforlanguageview.isValid();
			return isValid;
		} );

		return isValid;
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			isInitialValue = true;

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			isInitialValue = entitytermsforlanguageview.isInitialValue();
			return isInitialValue;
		} );

		return isInitialValue;
	},

	startEditing: function() {
		if ( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this.element.addClass( 'wb-edit' );

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			entitytermsforlanguageview.startEditing();
		} );

		this.updateInputSize();

		this._trigger( 'afterstartediting' );
	},

	/**
	 * @param {boolean} [dropValue]
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if ( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		dropValue = !!dropValue;

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		// TODO: This widget should not need to queue the requests of its encapsulated widgets.
		// However, the back-end produces edit conflicts when issuing multiple requests at once.
		// Remove queueing as soon as the back-end is fixed; see bug T74020.
		var $queue = $( {} ),
			eventNamespace = 'entitytermsforlanguagelistviewstopediting';

		/**
		 * @param {jQuery} $queue
		 * @param {jQuery.wikibase.entitytermsforlanguageview} entitytermsforlanguageview
		 * @param {boolean} dropValue
		 */
		function addStopEditToQueue( $queue, entitytermsforlanguageview, dropValue ) {
			$queue.queue( 'stopediting', function( next ) {
				entitytermsforlanguageview.element
				.one( 'entitytermsforlanguageviewafterstopediting.' + eventNamespace,
					function( event ) {
						entitytermsforlanguageview.element.off( '.' + eventNamespace );
						setTimeout( next, 0 );
					}
				)
				.one( 'entitytermsforlanguageviewtoggleerror.' + eventNamespace,
					function( event ) {
						entitytermsforlanguageview.element.off( '.' + eventNamespace );
						$queue.clearQueue();
						self._resetEditMode();
					}
				);
				entitytermsforlanguageview.stopEditing( dropValue );
			} );
		}

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			addStopEditToQueue(
				$queue,
				entitytermsforlanguageview,
				dropValue || entitytermsforlanguageview.isInitialValue()
			);
		} );

		$queue.queue( 'stopediting', function() {
			self._afterStopEditing( dropValue );
		} );

		$queue.dequeue( 'stopediting' );
	},

	_resetEditMode: function() {
		this.enable();

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			entitytermsforlanguageview.startEditing();
		} );
	},

	/**
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if ( !dropValue ) {
			this.options.value = this.value();
		}
		this._isInEditMode = false;
		this.enable();
		this.element.removeClass( 'wb-edit' );
		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	cancelEditing: function() {
		this.stopEditing( true );
	},

	/**
	 * Updates the size of the input boxes by triggering the inputautoexpand plugin's `expand()`
	 * function.
	 */
	updateInputSize: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );

			$.each( ['label', 'description', 'aliases'], function() {
				var $view = entitytermsforlanguageview['$' + this + 'view'],
					autoExpandInput = $view.find( 'input,textarea' ).data( 'inputautoexpand' );

				if ( autoExpandInput ) {
					autoExpandInput.options( {
						maxWidth: $view.width()
					} );
					autoExpandInput.expand( true );
				}
			} );
		} );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		var listview = this.$listview.data( 'listview' ),
			$items = listview.items();

		if ( $items.length ) {
			listview.listItemAdapter().liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	},

	/**
	 * Applies/Removes error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if ( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.removeError();
			this._trigger( 'toggleerror' );
		}
	},

	removeError: function() {
		this.element.removeClass( 'wb-error' );

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			entitytermsforlanguageview.removeError();
		} );
	},

	/**
	 * @param {Object[]} [value]
	 * @return {Object[]|*}
	 */
	value: function( value ) {
		if ( value !== undefined ) {
			return this.option( 'value', value );
		}

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		value = [];

		listview.items().each( function() {
			var entitytermsforlanguageview = lia.liInstance( $( this ) );
			value.push( entitytermsforlanguageview.value() );
		} );

		return value;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' ) {
			throw new Error( 'Impossible to set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if ( key === 'disabled' ) {
			this.$listview.data( 'listview' ).option( key, value );
		}

		return response;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
