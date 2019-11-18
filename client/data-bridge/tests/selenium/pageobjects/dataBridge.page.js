const Page = require( 'wdio-mediawiki/Page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	ForwardCompatUtil = require( '../ForwardCompatUtil' );

class DataBridgePage extends Page {
	static get OOUI() {
		return '.oo-ui-dialog';
	}

	static get ROOT() {
		return '#data-bridge-app';
	}

	static get HEADER_ELEMENTS() {
		const headerClass = '.wb-ui-processdialog-header';
		return {
			SAVE: `${headerClass} .wb-ui-event-emitting-button--primaryProgressive`,
			CANCEL: `${headerClass} .wb-ui-event-emitting-button--cancel`,
		};
	}

	static get ROOT_SWITCH() {
		return {
			INIT: '.wb-db-init',
			ERROR: '.wb-db-error',
			BRIDGE: '.wb-db-bridge',
		};
	}

	static get STRING_VALUE() {
		return '.wb-db-stringValue .wb-db-stringValue__input';
	}

	static get PROPERTY_LABEL() {
		return '.wb-db-PropertyLabel';
	}

	static get REFERENCES_SECTION() {
		return '.wb-db-references';
	}

	static get REFERENCE() {
		return '.wb-db-references__listItem';
	}

	getDummyTitle() {
		return Util.getTestString( 'Talk:Data-bridge-test-page-' );
	}

	open( title ) {
		super.openTitle( title );
		ForwardCompatUtil.waitForModuleState( 'wikibase.client.data-bridge.app', 'ready', 10000 );
	}

	openBridgeOnPage( title ) {
		this.open( title );
		this.overloadedLink.click();
		this.app.waitForDisplayed( 10000 );
	}

	get overloadedLink() {
		return $( 'a=Edit this on Wikidata' );
	}

	get dialog() {
		return $( '.oo-ui-dialog' );
	}

	get app() {
		return $( '#data-bridge-app' );
	}

	get saveButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.SAVE}`
		);
	}

	get cancelButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.CANCEL}`
		);
	}

	get int() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.INIT}`
		);
	}

	get error() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.ERROR}`
		);
	}

	get bridge() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}`
		);
	}

	get value() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE} ${DataBridgePage.STRING_VALUE}`
		);
	}

	get propertyLabel() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE} ${DataBridgePage.PROPERTY_LABEL}`
		);
	}

	nthReference( n ) {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}
				${DataBridgePage.REFERENCES_SECTION} ${DataBridgePage.REFERENCE}:nth-child( ${n} )`
		);
	}
}

module.exports = new DataBridgePage();
