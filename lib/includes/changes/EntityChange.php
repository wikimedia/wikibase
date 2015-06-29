<?php

namespace Wikibase;

use MWException;
use RecentChange;
use Revision;
use RuntimeException;
use User;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\BasicEntityIdParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;

/**
 * Represents a change for an entity; to be extended by various change subtypes
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChange extends DiffChange {

	const UPDATE =  'update';
	const ADD =     'add';
	const REMOVE =  'remove';
	const RESTORE = 'restore';

	/**
	 * @var EntityId|null
	 */
	private $entityId = null;

	/**
	 * @see ORMRow::setField
	 *
	 * Overwritten to force lower case object_id
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws MWException
	 */
	public function setField( $name, $value ) {
		if ( $name === 'object_id' && is_string( $value ) ) {
			//NOTE: for compatibility with earlier versions, use lower case IDs in the database.
			$value = strtolower( $value );
		}

		parent::setField( $name, $value );
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->getField( 'type' );
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		if ( !$this->entityId && $this->hasField( 'object_id' ) ) {
			// FIXME: this should be an injected EntityIdParser
			$idParser = new BasicEntityIdParser();
			$this->entityId = $idParser->parse( $this->getObjectId() );
		}

		return $this->entityId;
	}

	/**
	 * @return string
	 */
	public function getAction() {
		list( , $action ) = explode( '~', $this->getType(), 2 );

		return $action;
	}

	/**
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return array
	 */
	public function getMetadata( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( array_key_exists( 'metadata', $info ) ) {
			return $info['metadata'];
		}

		return false;
	}

	/**
	 * @param array $metadata
	 *
	 * @return bool
	 */
	public function setMetadata( array $metadata ) {
		$validKeys = array(
			'page_id',
			'bot',
			'rev_id',
			'parent_id',
			'user_text',
			'comment',
		);

		if ( is_array( $metadata ) ) {
			foreach ( array_keys( $metadata ) as $key ) {
				if ( !in_array( $key, $validKeys ) ) {
					unset( $metadata[$key] );
				}
			}

			if ( !array_key_exists( 'comment', $metadata ) ) {
				$metadata['comment'] = $this->getComment();
			}

			$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
			$info['metadata'] = $metadata;
			$this->setField( 'info', $info );

			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getComment() {
		$meta = $this->getMetadata();

		if ( empty( $meta ) || !isset( $meta['comment'] ) ) {
			// Messages: wikibase-comment-add, wikibase-comment-remove, wikibase-comment-linked,
			// wikibase-comment-unlink, wikibase-comment-restore, wikibase-comment-update
			return 'wikibase-comment-' . $this->getAction();
		}

		return $meta['comment'];
	}

	/**
	 * @see ChangeRow::postConstruct
	 */
	protected function postConstruct() {
		// This implementation should not set the type field.
	}

	/**
	 * @param RecentChange $rc
	 *
	 * @todo rename to setRecentChangeInfo
	 */
	public function setMetadataFromRC( RecentChange $rc ) {
		$this->setMetadata( array(
			'user_text' => $rc->getAttribute( 'rc_user_text' ),
			'bot' => $rc->getAttribute( 'rc_bot' ),
			'page_id' => $rc->getAttribute( 'rc_cur_id' ),
			'rev_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'parent_id' => $rc->getAttribute( 'rc_last_oldid' ),
			'comment' => '',
		) );
	}

	/**
	 * @param User $user
	 *
	 * @todo rename to setUserInfo, set fields too.
	 */
	public function setMetadataFromUser( User $user ) {
		$this->setMetadata( array(
			'user_text' => $user->getName(),
			'page_id' => 0,
			'rev_id' => 0,
			'parent_id' => 0,
		) );
	}

	/**
	 * @since 0.5
	 *
	 * @param Revision $revision
	 */
	public function setRevisionInfo( Revision $revision ) {
		/* @var EntityContent $content */
		$content = $revision->getContent();
		$entityId = $content->getEntityId();

		$this->setFields( array(
			'revision_id' => $revision->getId(),
			'user_id' => $revision->getUser(),
			'object_id' => $entityId->getSerialization(),
			'time' => $revision->getTimestamp(),
		) );

		$this->setMetadata( array(
			'comment' => $revision->getComment(),
			'page_id' => $revision->getPage(),
			'rev_id' => $revision->getId(),
			'parent_id' => $revision->getParentId(),
			'user_text' => $revision->getUserText(),
		) );
	}

	/**
	 * @param int $userId
	 *
	 * @todo Merge into future setUserInfo.
	 */
	public function setUserId( $userId ) {
		$this->setField( 'user_id', $userId );
	}

	/**
	 * @param string $timestamp Timestamp in TS_MW format
	 */
	public function setTimestamp( $timestamp ) {
		$this->setField( 'time', $timestamp );
	}

	/**
	 * Returns a human readable string representation of the change. Useful for logging and debugging.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function __toString() {
		$string = get_class( $this );
		$string .= ': ';

		$fields = $this->getFields();
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$meta = $this->getMetadata();

		if ( is_array( $info ) ) {
			$fields = array_merge( $fields, $info );
		}

		if ( is_array( $meta ) ) {
			$fields = array_merge( $fields, $meta );
		}

		foreach ( $fields as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				unset( $fields[$key] );
			}
		}

		ksort( $fields );

		$string .= preg_replace( '/\s+/s', ' ', var_export( $fields, true ) );
		return $string;
	}

	/**
	 * @see DiffChange::arrayalizeObjects
	 *
	 * Overwritten to handle Statement objects.
	 *
	 * @since 0.4
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function arrayalizeObjects( $data ) {
		$data = parent::arrayalizeObjects( $data );

		if ( $data instanceof Statement ) {
			$array = $this->getStatementSerializer()->serialize( $data );
			$array['_claimclass_'] = get_class( $data );

			return $array;
		}

		return $data;
	}

	private function getStatementSerializer() {
		// FIXME: the change row system needs to be reworked to either allow for sane injection
		// or to avoid this kind of configuration dependent tasks.
		if ( defined( 'WB_VERSION' ) ) {
			return WikibaseRepo::getDefaultInstance()->getInternalStatementSerializer();
		} elseif ( defined( 'WBC_VERSION' ) ) {
			throw new RuntimeException( 'Cannot serialize statements on the client' );
		} else {
			throw new RuntimeException( 'Need either client or repo loaded' );
		}
	}

	private function getStatementDeserializer() {
		// FIXME: the change row system needs to be reworked to either allow for sane injection
		// or to avoid this kind of configuration dependent tasks.
		if ( defined( 'WB_VERSION' ) ) {
			return WikibaseRepo::getDefaultInstance()->getInternalStatementDeserializer();
		} elseif ( defined( 'WBC_VERSION' ) ) {
			return WikibaseClient::getDefaultInstance()->getInternalStatementDeserializer();
		} else {
			throw new RuntimeException( 'Need either client or repo loaded' );
		}
	}

	/**
	 * @see DiffChange::objectifyArrays
	 *
	 * Overwritten to handle Statement objects.
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function objectifyArrays( array $data ) {
		$data = parent::objectifyArrays( $data );

		if ( is_array( $data ) && isset( $data['_claimclass_'] ) ) {
			$class = $data['_claimclass_'];

			if ( $class === 'Wikibase\DataModel\Statement\Statement'
				|| is_subclass_of( $class, 'Wikibase\DataModel\Statement\Statement' )
			) {
				unset( $data['_claimclass_'] );

				return $this->getStatementDeserializer()->deserialize( $data );
			}
		}

		return $data;
	}

}
