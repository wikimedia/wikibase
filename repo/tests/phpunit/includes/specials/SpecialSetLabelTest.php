<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetLabel;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetLabel
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetLabelTest extends SpecialModifyTermTestCase {

	protected function newSpecialPage() {
		return new SpecialSetLabel();
	}

}
