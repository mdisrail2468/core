<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test;

use OC\Repair;
use OCP\Migration\IRepairStep;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class RepairTest
 *
 * @group DB
 * @package Test
 */
class GetRepairTest extends TestCase {
	/** @var  IRepairStep | \PHPUnit_Framework_MockObject_MockObject*/
	private $repairSteps;
	/** @var  EventDispatcher | \PHPUnit_Framework_MockObject_MockObject */
	private $eventDispatcher;
	/** @var  Repair */
	private $repair;

	protected function setUp() {
		parent::setUp();

		$this->repairSteps = $this->createMock(IRepairStep::class);
		$this->eventDispatcher = $this->createMock(EventDispatcher::class);
		$this->repair = new Repair($this->repairSteps, $this->eventDispatcher);
	}

	public function providerGetRepairSteps() {
		return [
			['10.0.3'],
			['10.0.4'],
			['10.0.4.1'],
			['10.0.5']
		];
	}

	/**
	 * @dataProvider providerGetRepairSteps
	 */
	public function testGetRepairSteps($version) {
		\OC::$server->getConfig()->setSystemValue('version', $version);
		$result = $this->invokePrivate($this->repair, 'getRepairSteps', []);
		if (\version_compare($version, '10.0.4', '<')) {
			$this->assertInstanceOf(Repair\RepairMimeTypes::class, $result[0]);
			$this->assertInstanceOf(Repair\RepairMismatchFileCachePath::class, $result[1]);
			$this->assertInstanceOf(Repair\FillETags::class, $result[2]);
			$this->assertEquals(15, \count($result));
		} else {
			$this->assertInstanceOf(Repair\RepairMimeTypes::class, $result[0]);
			$this->assertInstanceOf(Repair\FillETags::class, $result[1]);
			$this->assertEquals(14, \count($result));
		}
	}
}
