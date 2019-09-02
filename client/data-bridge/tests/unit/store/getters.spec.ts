import EditFlow from '@/definitions/EditFlow';
import { ENTITY_ID } from '@/store/entity/getterTypes';
import { getters } from '@/store/getters';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import newApplicationState from './newApplicationState';
import ApplicationStatus from '@/definitions/ApplicationStatus';

describe( 'root/getters', () => {
	it( 'has an targetProperty', () => {
		const targetProperty = 'P23';
		const applicationState = newApplicationState( { targetProperty } );
		expect( getters.targetProperty(
			applicationState, null, applicationState, null,
		) ).toBe( targetProperty );
	} );

	it( 'has an editFlow', () => {
		const editFlow = EditFlow.OVERWRITE;
		const applicationState = newApplicationState( { editFlow } );
		expect( getters.editFlow(
			applicationState, null, applicationState, null,
		) ).toBe( editFlow );
	} );

	it( 'has an application status', () => {
		const applicationStatus = ApplicationStatus.READY;
		const applicationState = newApplicationState( { applicationStatus } );
		expect( getters.applicationStatus(
			applicationState, null, applicationState, null,
		) ).toBe( ApplicationStatus.READY );
	} );

	describe( 'targetValue', () => {
		it( 'returns null if the application is in error state', () => {
			const applicationState = newApplicationState( {
				applicationStatus: ApplicationStatus.ERROR,
			} );
			expect( getters.targetValue(
				applicationState, null, applicationState, null,
			) ).toBeNull();
		} );

		it( 'returns the target value', () => {
			const dataValue = { type: 'string', value: 'a string' };
			const targetProperty = 'P23';
			const entityId = 'Q42';
			const otherGetters = {
				targetProperty,
				[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ]: entityId,
				[ namespacedStoreEvent(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.dataValue,
				) ]: jest.fn( () => {
					return dataValue;
				} ),
			};

			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
			} );

			expect( getters.targetValue(
				applicationState, otherGetters, applicationState, null,
			) ).toBe( dataValue );
			expect(
				otherGetters[ namespacedStoreEvent(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.dataValue,
				) ],
			).toHaveBeenCalledWith( {
				entityId,
				propertyId: targetProperty,
				index: 0,
			} );
		} );
	} );
} );