<?php

namespace Wikibase\Repo;

use Wikibase\Change;
use Wikibase\ChunkAccess;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemChange;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikibase\Repo\Notifications\ChangeNotificationSender;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikibase\Store\SubscriptionLookup;

/**
 * Interactor class for dispatching change notifications to client wikis via the job queue.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeDispatcher {

	/**
	 * @var int: the number of changes to pass to a client wiki at once.
	 */
	private $batchSize = 1000;

	/**
	 * @var int: factor used to compute the number of changes to load from the changes table at once
	 *           based on $this->batchSize.
	 */
	private $batchChunkFactor = 3;

	/**
	 * @var bool: whether output should be version.
	 */
	private $verbose = false;

	/**
	 * @var ChangeNotificationSender
	 */
	private $notificationSender;

	/**
	 * @var ChangeDispatchCoordinator
	 */
	private $coordinator;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var ChunkAccess<Change> : access to the changes tablchangesCachechangesCachee
	 */
	private $chunkedChangesAccess;

	/**
	 * @var SubscriptionLookup
	 */
	private $subscriptionLookup;

	/**
	 * @param ChangeDispatchCoordinator $coordinator
	 * @param ChangeNotificationSender $notificationSender
	 * @param ChunkAccess<Change> $chunkedChangesAccess
	 * @param SubscriptionLookup $subscriptionLookup
	 */
	public function __construct(
		ChangeDispatchCoordinator $coordinator,
		ChangeNotificationSender $notificationSender,
		ChunkAccess $chunkedChangesAccess,
		SubscriptionLookup $subscriptionLookup
	) {
		$this->coordinator = $coordinator;
		$this->notificationSender = $notificationSender;
		$this->subscriptionLookup = $subscriptionLookup;

		$this->chunkedChangesAccess = $chunkedChangesAccess;

		$this->exceptionHandler = new LogWarningExceptionHandler();
		$this->messageReporter = new NullMessageReporter();
	}

	/**
	 * @return boolean
	 */
	public function isVerbose() {
		return $this->verbose;
	}

	/**
	 * @param boolean $verbose
	 */
	public function setVerbose( $verbose ) {
		$this->verbose = $verbose;
	}

	/**
	 * @return MessageReporter
	 */
	public function getMessageReporter() {
		return $this->messageReporter;
	}

	/**
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @return ExceptionHandler
	 */
	public function getExceptionHandler() {
		return $this->exceptionHandler;
	}

	/**
	 * @param ExceptionHandler $exceptionHandler
	 */
	public function setExceptionHandler( $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @return int
	 */
	public function getBatchChunkFactor() {
		return $this->batchChunkFactor;
	}

	/**
	 * @param int $batchChunkFactor
	 */
	public function setBatchChunkFactor( $batchChunkFactor ) {
		$this->batchChunkFactor = $batchChunkFactor;
	}

	/**
	 * Selects a client wiki and locks it. If no suitable client wiki can be found,
	 * this method returns null.
	 *
	 * Note: this implementation will try a wiki from the list returned by getCandidateClients()
	 * at random. If all have been tried and failed, it returns null.
	 *
	 * @return array An associative array containing the state of the selected client wiki
	 *               (or null, if no target could be locked). Fields are:
	 *
	 * * chd_site:     the client wiki's global site ID
	 * * chd_db:       the client wiki's logical database name
	 * * chd_seen:     the last change ID processed for that client wiki
	 * * chd_touched:  timestamp giving the last time that client wiki was updated
	 * * chd_lock:     the name of a global lock currently active for that client wiki
	 *
	 * @throws \MWException if no available client wiki could be found.
	 *
	 * @see releaseWiki()
	 */
	public function selectClient() {
		return $this->coordinator->selectClient();
	}

	/**
	 * Performs one update pass. This involves the following steps:
	 *
	 * 1) Get a batch of changes for the client wiki.
	 * 2) Post a notification job to the client wiki's job queue.
	 * 3) Update the dispatch log for the client wiki, and release it.
	 *
	 * @param array $wikiState the dispatch state of a client wiki, as returned by lockClient()
	 * @return int The number of changes dispatched
	 */
	public function dispatchTo( $wikiState ) {
		$siteID = $wikiState['chd_site'];
		$after = intval( $wikiState['chd_seen'] );

		// get relevant changes
		$this->trace( "Finding pending changes for $siteID" );
		list( $changes, $continueAfter ) = $this->getPendingChanges( $siteID, $after );

		$n = count( $changes );

		if ( $n > 0 ) {
			$this->trace( "Dispatching $n changes to $siteID, up to #$continueAfter" );

			// notify the client wiki about the changes
			$this->notificationSender->sendNotification( $siteID, $changes );
		}

		$wikiState['chd_seen'] = $continueAfter;

		$this->coordinator->releaseClient( $wikiState );

		if ( $n === 0 ) {
			$this->trace( "Posted no changes to $siteID (nothing to do). "
						. "Next ID is $continueAfter." );
		} else {
			/* @var Change $last */
			$last = end( $changes );

			$this->log( "Posted $n changes to $siteID, "
				. "up to ID " . $last->getId() . ", timestamp " . $last->getTime() . ". "
				. "Lag is " . $last->getAge() . " seconds. "
				. "Next ID is $continueAfter." );
		}

		return $n;
	}

	/**
	 * Returns a batch of changes for the given client wiki, starting from the given position
	 * in the wb_changes table. The changes may be filtered to only include those changes that
	 * are relevant to the given client wiki. The number of changes returned by this method
	 * is limited by $this->batchSize. Changes are returned with IDs in ascending order.
	 *
	 * @note: due to programmatic filtering, this method may use multiple database queries to
	 * collect the changes for the next batch. The number of requests needed can be adjusted
	 * using $this->batchChunkFactor (via the 'dispatchBatchChunkFactor' setting).
	 *
	 * @param string $siteID:    The client wiki's global site identifier, as used by sitelinks.
	 * @param int $after:  The last change ID processed by a previous run. All changes returned
	 *                     will have an ID greater than $after.
	 *
	 * @return array ( $batch, $seen ), where $batch is a list of Change objects, and $seen
	 *         if the ID of the last change considered for the batch (even if that was filtered out),
	 *         for use as a continuation marker.
	 */
	public function getPendingChanges( $siteID, $after ) {
		// Loop until we have a full batch of size $this->batchSize,
		// or there are no more changes to process.

		//NOTE: we could try to filter the changes directly in the DB, but
		//      that will no longer work once we have a client side usage tracking table
		//      for free-form use.

		$batch = array();
		$batchSize = 0;
		$chunkSize = $this->batchSize * $this->batchChunkFactor;

		// Track the change ID from which the next pass should start.
		// Note that this is non-trivial due to programmatic filtering.
		$lastIdSeen = $after;

		while ( $batchSize < $this->batchSize ) {
			// get a chunk of changes
			$chunk = $this->chunkedChangesAccess->loadChunk( $after+1, $chunkSize );

			if ( empty( $chunk ) ) {
				break; // no more changes
			}

			// start the next round here
			$last = end( $chunk );
			$after = $last->getId();
			reset( $chunk ); // don't leave the array pointer messy.

			// filter the changes in the chunk and add the result to the batch
			$remaining = $this->batchSize - $batchSize;
			list( $filtered, $lastIdSeen ) = $this->filterChanges( $siteID, $chunk, $remaining );

			$batch = array_merge( $batch, $filtered );
			$batchSize = count( $batch );

			//XXX: We could try to adapt $chunkSize based on ratio of changes that get filtered out:
			//     $chunkSize = ( $this->batchSize - count( $batch ) ) * ( count_before / count_after );
		}

		$this->trace( "Got " . count( $batch ) . " pending changes. " );

		return array( $batch, $lastIdSeen );
	}

	/**
	 * Checks whether the given Change is somehow relevant to the given wiki site.
	 *
	 * In particular this check whether the Change modifies any sitelink that refers to the
	 * given wiki site.
	 *
	 * @note: this does not check whether the entity that was changes is or is not at all
	 *        connected with (resp. used on) the target wiki.
	 *
	 * @param Change $change the change to examine.
	 * @param string $siteID the site to consider.
	 *
	 * @return bool
	 */
	private function isRelevantChange( Change $change, $siteID ) {
		if ( $change instanceof ItemChange && !$change->isEmpty() ) {
			$siteLinkDiff = $change->getSiteLinkDiff();

			if ( isset( $siteLinkDiff[ $siteID ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filters a list of changes, removing changes not relevant to the given client wiki.
	 *
	 * Currently, we only keep ItemChanges for items that have a sitelink to the
	 * target client wiki.
	 *
	 * @param string   $siteID : The client wiki's global site identifier, as used by sitelinks.
	 * @param Change[] $changes: The list of changes to filter.
	 * @param int      $limit:   The max number of changes to return
	 *
	 * @return array ( $batch, $seen ), where $batch is the filtered list of Change objects,
	 *         and $seen if the ID of the last change considered for the batch
	 *         (even if that was filtered out), for use as a continuation marker.
	 */
	private function filterChanges( $siteID, $changes, $limit ) {
		// collect all item IDs mentioned in the changes
		$itemSet = array();
		foreach ( $changes as $change ) {
			if ( $change instanceof ItemChange ) {
				$id = $change->getEntityId();
				$itemId = $id->getNumericId();
				$itemSet[$itemId] = $id;
			}
		}

		$this->trace( "Checking sitelinks to $siteID for " . count( $itemSet ) . " items." );

		$linkedItems = $this->subscriptionLookup->getSubscriptions( $siteID, $itemSet );
		$linkedItems = $this->reIndexEntityIds( $linkedItems );

		$this->trace( "Retaining changes for " . count( $linkedItems ) . " relevant items." );

		// find all changes that relate to an item that has a sitelink to $siteID.
		$filteredChanges = array();
		$numberOfChangesFound = 0;
		$lastIdSeen = 0;
		foreach ( $changes as $change ) {
			$lastIdSeen = $change->getId();

			if ( $change instanceof ItemChange) {
				$itemId = $change->getEntityId()->getNumericId();

				// The change is relevant if it alters any sitelinks referring to $siteID,
				// or the item currently links to $siteID.
				if ( isset( $linkedItems[$itemId] )
					|| $this->isRelevantChange( $change, $siteID ) !== null ) {

					$filteredChanges[] = $change;
					$numberOfChangesFound++;
				}
			}

			if ( $numberOfChangesFound >= $limit ) {
				break;
			}
		}

		$this->trace( "Found " . count( $filteredChanges ) . " changes for items with relevant sitelinks." );

		return array( $filteredChanges, $lastIdSeen );
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return ItemId[] The ItemIds from EntityId[], keyed by numeric id.
	 */
	private function reIndexEntityIds( array $entityIds ) {
		$reindexed = array();

		foreach ( $entityIds as $id ) {
			if ( $id instanceof ItemId ) {
				$key = $id->getNumericId();
				$reindexed[$key] = $id;
			}
		}

		return $reindexed;
	}

	/**
	 * Log a message if verbose mode is enabled
	 *
	 * @param string $message
	 */
	private function trace( $message ) {
		if ( $this->verbose ) {
			$this->log( "    " . $message );
		}
	}

	private function log( $message ) {
		$this->messageReporter->reportMessage( $message );
	}

	/**
	 * @return ChangeDispatchCoordinator
	 */
	public function getDispatchCoordinator() {
		return $this->coordinator;
	}
}
