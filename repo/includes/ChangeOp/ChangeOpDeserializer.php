<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Interface for services that construct ChangeOps from a JSON style array structure
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface ChangeOpDeserializer {

	/**
	 * @param array $data an array of data to deserialize. For example:
	 *        [ 'label' => [ 'zh' => [ 'remove' ], 'de' => [ 'value' => 'Foo' ] ] ]
	 * @return ChangeOps
	 */
	public function deserialize( array $serialization );

}
