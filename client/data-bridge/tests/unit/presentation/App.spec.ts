import EntityRevision from '@/datamodel/EntityRevision';
import { ErrorTypes } from '@/definitions/ApplicationError';
import Vuex, { Store } from 'vuex';
import Entities from '@/mock-data/data/Q42.data.json';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import Application from '@/store/Application';
import Events from '@/events';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditFlow from '@/definitions/EditFlow';
import DataBridge from '@/presentation/components/DataBridge.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import Loading from '@/presentation/components/Loading.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import hotUpdateDeep from '@wmde/vuex-helpers/dist/hotUpdateDeep';
import MessageKeys from '@/definitions/MessageKeys';
import EntityId from '@/datamodel/EntityId';
import { calledWithHTMLElement } from '../../util/assertions';
import newMockServiceContainer from '../services/newMockServiceContainer';
import License from '@/presentation/components/License.vue';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	let store: Store<Application>;
	let entityId: EntityId;
	let propertyId: string;
	let editFlow: EditFlow;

	beforeEach( async () => {
		entityId = 'Q42';
		propertyId = 'P373';
		editFlow = EditFlow.OVERWRITE;
		( Entities.entities.Q42 as any ).statements = Entities.entities.Q42.claims;

		store = createStore( newMockServiceContainer( {
			'readingEntityRepository': {
				getEntity: () => Promise.resolve( {
					revisionId: 984899757,
					entity: Entities.entities.Q42,
				} as any ),
			},
			'writingEntityRepository': {
				saveEntity: ( entity: EntityRevision ) => Promise.resolve( new EntityRevision(
					entity.entity,
					entity.revisionId + 1,
				) ),
			},
			'entityLabelRepository': {
				getLabel: () => Promise.reject(),
			},
			'wikibaseRepoConfigRepository': {
				getRepoConfiguration: () => Promise.resolve( {
					dataTypeLimits: {
						string: {
							maxLength: 200,
						},
					},
				} ),
			},
			'referencesRenderingRepository': {
				getRenderedReferences: () => Promise.resolve( [] ),
			},
			'propertyDatatypeRepository': {
				getDataType: jest.fn().mockResolvedValue( 'string' ),
			},
			'tracker': {
				trackPropertyDatatype: jest.fn(),
			},
			'editAuthorizationChecker': {
				canUseBridgeForItemAndPage: () => Promise.resolve( [] ),
			},
		} ) );

		const information = {
			entityId,
			propertyId,
			editFlow,
			client: {
				usePublish: true,
			},
		};

		await store.dispatch( 'initBridge', information );
	} );

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		expect( wrapper.classes() ).toContain( 'wb-db-app' );
	} );

	it( 'shows the header with title', () => {
		const titleMessage = 'he ho';
		const messageGet = jest.fn().mockReturnValue( titleMessage );
		const wrapper = shallowMount( App, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			stubs: { ProcessDialogHeader },
		} );

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( wrapper.find( ProcessDialogHeader ).exists() ).toBe( true );
		expect( messageGet ).toHaveBeenCalledWith(
			MessageKeys.BRIDGE_DIALOG_TITLE,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${propertyId}</span>`,
		);
		expect( wrapper.find( 'h1' ).text() ).toBe( titleMessage );
	} );

	describe( 'save button rendering', () => {
		it( 'renders the save button using the SAVE_CHANGES message', () => {
			const saveMessage = 'go go go';
			const messageGet = jest.fn(
				( key: string ) => {
					if ( key === MessageKeys.SAVE_CHANGES ) {
						return saveMessage;
					}

					return '';
				},
			);

			const wrapper = shallowMount( App, {
				store,
				localVue,
				mocks: {
					$bridgeConfig: { usePublish: false },
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				stubs: { ProcessDialogHeader, EventEmittingButton },
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.SAVE_CHANGES );
			const button = wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' );
			expect( button.props( 'message' ) ).toBe( saveMessage );
		} );

		it( 'renders the save button using the PUBLISH_CHANGES message', () => {
			const publishMessage = 'run run run';
			const messageGet = jest.fn(
				( key: string ) => {
					if ( key === MessageKeys.PUBLISH_CHANGES ) {
						return publishMessage;
					}

					return '';
				},
			);

			const wrapper = shallowMount( App, {
				store,
				localVue,
				mocks: {
					$bridgeConfig: { usePublish: true },
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				stubs: { ProcessDialogHeader, EventEmittingButton },
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.PUBLISH_CHANGES );
			const button = wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' );
			expect( button.props( 'message' ) ).toBe( publishMessage );
		} );
	} );

	it( 'shows License on first save button click and saves on second save button click', async () => {
		const bridgeSave = jest.fn();
		const localStore = hotUpdateDeep( store, {
			actions: {
				saveBridge: bridgeSave,
			},
		} );
		localStore.commit( 'setApplicationStatus', ApplicationStatus.READY );
		const wrapper = shallowMount( App, {
			store: localStore,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
		await localVue.nextTick();
		expect( bridgeSave ).not.toHaveBeenCalled();
		expect( wrapper.find( License ).exists() ).toBe( true );

		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
		await localVue.nextTick();
		expect( bridgeSave ).toHaveBeenCalledTimes( 1 );
		expect( wrapper.emitted( Events.onSaved ) ).toBeTruthy();
	} );

	it(
		'dismisses License on License\'s cancel button click and shows it again on next save button click',
		async () => {
			const bridgeSave = jest.fn();
			const localStore = hotUpdateDeep( store, {
				actions: {
					saveBridge: bridgeSave,
				},
			} );
			localStore.commit( 'setApplicationStatus', ApplicationStatus.READY );
			const wrapper = shallowMount( App, {
				store: localStore,
				localVue,
				stubs: { ProcessDialogHeader, EventEmittingButton },
			} );

			await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
			await localVue.nextTick();
			expect( wrapper.find( License ).exists() ).toBe( true );

			await wrapper.find( License ).vm.$emit( 'cancel' );
			await localVue.nextTick();
			expect( wrapper.find( License ).exists() ).toBe( false );

			await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
			await localVue.nextTick();
			expect( bridgeSave ).not.toHaveBeenCalled();
			expect( wrapper.find( License ).exists() ).toBe( true );
		},
	);

	it( 'adds an overlay over DataBridge while showing the License', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
		await localVue.nextTick();

		expect( wrapper.find( DataBridge ).classes( 'wb-db-app__data-bridge--overlayed' ) ).toBe( true );
	} );

	it( 'adds an overlay over DataBridge during save state', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );

		expect( wrapper.find( DataBridge ).classes( 'wb-db-app__data-bridge--overlayed' ) ).toBe( true );
	} );

	it( 'disables the save button while saving', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).trigger( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( Events.onSaved ) ).toBeFalsy();
	} );

	it( 'hides the save button after changes are saved', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVED );
		expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
	} );

	it( 'renders the cancel button using the CANCEL message', () => {
		const cancelMessage = 'cancel that';
		const messageGet = jest.fn().mockReturnValue( cancelMessage );
		const wrapper = shallowMount( App, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.CANCEL );
		const button = wrapper.find( '.wb-ui-event-emitting-button--cancel' );
		expect( button.props( 'message' ) ).toBe( cancelMessage );
	} );

	it( 'cancels on cancel button click', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--cancel' ).vm.$emit( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( Events.onCancel ) ).toBeTruthy();
	} );

	it( 'disables cancel while in saving state', async () => {
		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--cancel' ).trigger( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( Events.onCancel ) ).toBeFalsy();
	} );

	describe( 'component switch', () => {

		describe( 'if there is an error', () => {
			it( 'mounts ErrorWrapper', () => {
				store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( ErrorWrapper ).exists() ).toBe( true );
			} );

			it( 'doesn\'t show the save button ', () => {
				store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
				const wrapper = shallowMount( App, {
					store,
					localVue,
					stubs: { ProcessDialogHeader, EventEmittingButton },
				} );

				expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
			} );
		} );

		describe( 'outside of the error scenario', () => {
			it( 'mounts Loading & passes DataBridge to it', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).exists() ).toBe( true );
				expect( wrapper.find( Loading ).find( DataBridge ).exists() ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is not ready', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.INITIALIZING );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).props( 'isInitializing' ) ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is attempting saving', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).props( 'isSaving' ) ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is ready', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).props( 'isInitializing' ) ).toBe( false );
				expect( wrapper.find( Loading ).props( 'isSaving' ) ).toBe( false );
			} );
		} );

	} );
} );
