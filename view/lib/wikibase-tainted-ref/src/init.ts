import MwWindow from '@/@types/mediawiki/MwWindow';
import MWHookHandler from '@/MWHookHandler';

const RL_COMMON_MODULE_NAME = 'wikibase.tainted-ref.common';
export default async (): Promise<void> => {
	const mwWindow = window as MwWindow;
	if ( mwWindow.mw.config.get( 'wbTaintedReferencesEnabled' ) ) {
		const require = await mwWindow.mw.loader.using( RL_COMMON_MODULE_NAME );
		const app = require( RL_COMMON_MODULE_NAME );
		const hookHandler = new MWHookHandler( mwWindow.mw.hook );
		mwWindow.mw.hook( 'wikibase.entityPage.entityView.rendered' ).add(
			() => { app.launch( hookHandler ); },
		);
	}
};
