import MwInitTracker from '@/mediawiki/MwInitTracker';

describe( 'MwInitTracker', () => {

	it( 'tracks a given propertyDataType', () => {
		const tracker = {
			increment: jest.fn(),
			recordTiming: jest.fn(),
		};
		const mwStartupTime = 10;
		const timeLinkListenersAttached = 13;
		const performanceMock: any = {
			now: jest.fn().mockReturnValue( timeLinkListenersAttached ),
			getEntriesByName: jest.fn().mockReturnValue( [ { startTime: mwStartupTime } ] ),
		};
		const initTracker = new MwInitTracker( tracker, performanceMock );

		initTracker.recordTimeToLinkListenersAttached();

		expect( performanceMock.getEntriesByName ).toHaveBeenCalledWith( 'mwStartup' );
		expect( tracker.recordTiming ).toHaveBeenCalledWith(
			'timeToLinkListenersAttached',
			timeLinkListenersAttached - mwStartupTime,
		);
	} );

	it( 'is tracking the time it takes to open the modal after a click', () => {
		const tracker = {
			increment: jest.fn(),
			recordTiming: jest.fn(),
		};
		const performanceMock: any = {
			now: jest.fn(),
		};
		const timeAtClick = 10;
		const timeAtBridgeOpening = 15;
		performanceMock.now.mockReturnValueOnce( timeAtClick );
		performanceMock.now.mockReturnValueOnce( timeAtBridgeOpening );
		const initTracker = new MwInitTracker( tracker, performanceMock );

		const finishTracker = initTracker.startClickDelayTracker();
		expect( performanceMock.now ).toHaveBeenCalled();

		finishTracker();
		expect( performanceMock.now ).toHaveBeenCalledTimes( 2 );
		expect( tracker.recordTiming ).toHaveBeenCalledWith(
			'clickDelay',
			timeAtBridgeOpening - timeAtClick,
		);
	} );

} );
