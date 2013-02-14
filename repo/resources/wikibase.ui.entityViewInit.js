/**
 * JavaScript for 'wikibase' extension, initializing some stuff when ready
 * @todo: this might not be necessary or only for ui stuff when we add more js modules!
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */

( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		// remove HTML edit links with links to special pages
		// for site-links we don't want to remove the table cell representing the edit section
		$( 'td.wb-editsection' ).empty();
		// for all other values we remove the whole edit section
		$( 'span.wb-editsection, div.wb-editsection' ).remove();

		// remove all infos about empty values which are displayed in non-JS
		$( '.wb-value-empty' ).empty().removeClass( 'wb-value-empty' );

		// add an edit tool for the main label. This will be integrated into the heading nicely:
		if ( $( '.wb-firstHeading' ).length ) { // Special pages do not have a custom wb heading
			var labelEditTool = new wb.ui.LabelEditTool( $( '.wb-firstHeading' )[0] );
			var editableLabel = labelEditTool.getValues( true )[0]; // [0] will always be set

			// make sure we update the 'title' tag of the page when label changes
			editableLabel.on( 'afterStopEditing', function() {
				var value;

				if( editableLabel.isEmpty() ) {
					value = mw.config.get( 'wgTitle' );
				} else {
					value = editableLabel.getValue()[0];
				}
				value += ' - ' + mw.config.get( 'wgSiteName' );

				// update 'title' tag
				$( 'html title' ).text( value );
			} );
		}

		// add an edit tool for all properties in the data view:
		$( '.wb-property-container' ).each( function() {
			// TODO: Make this nicer when we have implemented the data model
			if( $( this ).children( '.wb-property-container-key' ).attr( 'title') === 'description' ) {
				new wb.ui.DescriptionEditTool( this );
			} else {
				new wb.ui.PropertyEditTool( this );
			}
		} );

		$( 'tr.wb-terms-label, tr.wb-terms-description' ).each( function() {
			var $termsRow = $( this ),
				editTool = wb.ui.PropertyEditTool[
					$termsRow.hasClass( 'wb-terms-label' )
						? 'EditableLabel'
						: 'EditableDescription'
				],
				toolbar = new wb.ui.Toolbar(),
				editGroup = new wb.ui.Toolbar.EditGroup();

			toolbar.addElement( editGroup );
			toolbar.editGroup = editGroup; // TODO: EditableLabel should not assume that this is set

			editTool.newFromDom( $termsRow, {}, toolbar );
		} );

		if( mw.config.get( 'wbEntity' ) !== null ) {
			var entityJSON = $.evalJSON( mw.config.get( 'wbEntity' ) ),
				usedEntitiesJSON = $.evalJSON( mw.config.get( 'wbUsedEntities' ) );

			// if there are no aliases yet, the DOM structure for creating new ones is created manually since it is not
			// needed for running the page without JS
			$( '.wb-aliases-empty' )
			.each( function() {
				$( this ).replaceWith( wikibase.ui.AliasesEditTool.getEmptyStructure() );
			} );

			// edit tool for aliases:
			$( '.wb-aliases' ).each( function() {
				new wb.ui.AliasesEditTool( this );
			} );

			// Information about used properties:
			$.each( usedEntitiesJSON, function( id, entity ) {
				entity.id = id; // we don't get that in the JSON but it is essential for wb.entities
				wb.entities[ id ] = entity;
			} );

			// Definition of the views entity:
			if ( entityJSON.claims !== undefined ) {
				$.each( entityJSON.claims, function( propertyId, claims ) {
					$.each( claims, function( i, claim ) {
						wb.entity.claims.push( wb.Claim.newFromJSON( claim ) );
					} );
				} );
			}
			wb.entity.id = entityJSON.id;
			wb.entity.type = entityJSON.type;

			$( '.wb-section-heading' ).remove();

			// BUILD CLAIMS VIEW:
			// Note: $.entityview() only works for claims right now, the goal is to use it for more
			var $claims = $( '.wb-claims' ).entityview( {
				value: wb.entity // only holds the claims of an entity page right now
			} );

			// removing site links heading to rebuild it with value counter
			$( 'table.wb-sitelinks' ).each( function() {
				$( this ).before(
					mw.template( 'wb-section-heading', mw.msg( 'wikibase-sitelinks' ), mw.config.get('wbEntityId')+'/sitelinks' )
					.append(
						$( '<span/>' )
						.attr( 'id', 'wb-item-' + mw.config.get('wbEntityId') + '-sitelinks-counter' )
						.addClass( 'wb-ui-propertyedittool-counter' )
					)
				);
				// actual initialization
				new wb.ui.SiteLinksEditTool( $( this ) );
			} );
		}

		// handle edit restrictions
		// TODO/FIXME: most about this system sucks, especially the part where the Button constructor is hacked to disable
		//             all buttons when this is fired. it also doesn't effect any edit tools added after this point and
		//             edit tool initialized above do not even know that they are disabled.
		if (
			mw.config.get( 'wgRestrictionEdit' ) !== null &&
			mw.config.get( 'wgRestrictionEdit' ).length === 1
		) { // editing is restricted
			if (
				$.inArray(
					mw.config.get( 'wgRestrictionEdit' )[0],
					mw.config.get( 'wgUserGroups' )
				) === -1
			) {
				// user is not allowed to edit
				$( wb ).triggerHandler( 'restrictEntityPageActions' );
			}
		}

		if ( mw.config.get( 'wbUserIsBlocked' ) || !mw.config.get( 'wbUserCanEdit' ) ) {
			$( wb ).triggerHandler( 'blockEntityPageActions' );
		}

		if( !mw.config.get( 'wbIsEditView' ) ) {
			// no need to implement a 'disableEntityPageActions' since hiding all the toolbars directly like this is
			// not really worse than hacking the Toolbar prototype to achieve this:
			$( '.wb-ui-toolbar' ).hide();
			$( 'body' ).addClass( 'wb-editing-disabled' );
			// make it even harder to edit stuff, e.g. if someone is trying to be smart, using
			// firebug to show hidden nodes again to click on them:
			$( wb ).triggerHandler( 'restrictEntityPageActions' );
		}

		$( wb ).on( 'startItemPageEditMode', function( event ) {
			// Display anonymous user edit warning:
			if ( mw.user && mw.user.isAnon() ) {
				mw.notify( mw.message( 'wikibase-anonymouseditwarning-' + wb.entity.type ) );
			}

			// add copyright warning to 'save' button if there is one:
			if( mw.config.exists( 'wbCopyrightWarning' ) ) {
				var $activeToolbar = $( '.wb-edit' )
					// label/description of EditableValue always in edit mode if empty, 2nd '.wb-edit'
					// on PropertyEditTool only appended when really being edited by the user though
					.not( '.wb-ui-propertyedittool-editablevalue-ineditmode' )
					.find( '.wb-ui-toolbar-editgroup-ineditmode' );

				if( !$activeToolbar.length ) {
					return; // no toolbar for some reason, just stop
				}

				var toolbar = $activeToolbar.data( 'wb-toolbar' );
				var tooltip = new wb.ui.Tooltip(
					// TODO: make _getTooltipParent public or add a Toolbar.Label.getElement()
					toolbar.btnSave._getTooltipParent(),
					{},
					$( '<span>' + mw.config.get( 'wbCopyrightWarning' ) + '</span>' ),
					// assuming the toolbar is used on the right side of some edit UI, we want to
					// point the tooltip away from that so it won't overlap with it:
					{ gravity: 'nw' }
				);
				toolbar.btnSave.setTooltip( tooltip );
				tooltip.show( true ); // show permanently, not just on hover!

				// destroy tooltip after edit mode gets closed again:
				$( wb ).one( 'stopItemPageEditMode', function( event ) {
					tooltip.destroy();
				} );
			}
		} );

		// remove loading spinner after JavaScript has kicked in
		$( '.wb-entity' ).fadeTo( 0, 1 );
		$( '.wb-entity-spinner' ).remove();

	} );

} )( jQuery, mediaWiki, wikibase );
