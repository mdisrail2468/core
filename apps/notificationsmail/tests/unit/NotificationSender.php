<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
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

namespace OCA\NotificationsMail\Tests;

use Test\TestCase;
use OCA\NotificationsMail\NotificationSender;
use OC\Mail\Message;
use OC\Mail\Mailer;
use OCP\Notification\IManager;
use OCP\IConfig;
use OCP\L10N\IFactory;

class NotificationSenderTest extends TestCase {
	/** @var NotificationSender */
	private $nsender;
	/** @var IManager */
	private $manager;
	/** @var Mailer */
	private $mailer;
	/** @var IConfig */
	private $config;
	/** @var IFactory */
	private $l10nFactory;

	protected function setUp() {
		parent::setUp();
		$this->manager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		// we have to use an implementation due to the "createMessage" method in the mailer
		$this->mailer = $this->getMockBuilder(Mailer::class)
			->setMethodsExcept(['createMessage'])
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->l10nFactory = $this->getMockBuilder(IFactory::class)
			->disableOriginalConstructor()
			->getMock();
		$this->nsender = new NotificationSender($this->manager, $this->mailer, $this->config, $this->l10nFactory);
	}

	public function emailProvider() {
		return [
			[['a@test.com', 'b@test.com']],
			[['a@x.invalid.com', 'z@x.invalid.com']],
			[['oöGm41l@test.com', 'huehue@invalid.com']],
			[['@b.test.com']],
			[['', null, 'valid@test.com']],
		];
	}

	/**
	 * @dataProvider emailProvider
	 */
	public function testValidateEmails(array $emails) {
		$pattern = '/^[a-zA-Z0-9][a-zA-Z0-9]*@test\.com$/';

		$this->mailer->method('validateMailAddress')
			->will($this->returnCallback(function($email) use ($pattern){
				return preg_match($pattern, $email) === 1;
		}));

		$expectedValid = [];
		$expectedInvalid = [];
		foreach ($emails as $email) {
			if (preg_match($pattern, $email) === 1) {
				$expectedValid[] = $email;
			} else {
				$expectedInvalid[] = $email;
			}
		}

		$result = $this->nsender->validateEmails($emails);
		$this->assertEquals($expectedValid, $result['valid']);
		$this->assertEquals($expectedInvalid, $result['invalid']);
	}

	public function testSendNotification() {
		$mockedNotification = $this->getMockBuilder('\OCP\Notification\INotification')->disableOriginalConstructor()->getMock();
		$mockedNotification->method('getUser')->willReturn('userTest1');
		$mockedNotification->method('getObjectType')->willReturn('test_obj_type');
		$mockedNotification->method('getObjectId')->willReturn('202');
		$mockedNotification->method('getParsedSubject')->willReturn('This is a parsed subject');
		$mockedNotification->method('getParsedMessage')->willReturn('Parsed message is this');

		$this->manager->method('prepare')->willReturn($mockedNotification);

		$mockedL10N = $this->getMockBuilder('\OCP\IL10N')->disableOriginalConstructor()->getMock();
		$mockedL10N->method('t')
			->will($this->returnCallback(function ($text, $params) {
				return vsprintf($text, $params);
		}));

		$this->l10nFactory->method('get')->willReturn($mockedL10N);
		$this->mailer->expects($this->once())->method('send');

		$this->config->method('getUserValue')
			->will($this->returnValueMap([
				['userTest1', 'notificationsmail', 'email_sending_option', 'never', 'always']
		]));

		$sentMessage = $this->nsender->sendNotification($mockedNotification, 'http://test.server/oc', ['test@example.com']);

		$this->assertEquals(['test@example.com' => null], $sentMessage->getTo());
		// check that the subject contains the server url
		$this->assertContains('http://test.server/oc', $sentMessage->getSubject());
		// check the notification id is also present in the subject
		$notifId = $mockedNotification->getObjectType() . "#" . $mockedNotification->getObjectId();
		$this->assertContains($notifId, $sentMessage->getSubject());

		// notification's subject and message must be present in the email body, as well as the server url
		$this->assertContains($mockedNotification->getParsedSubject(), $sentMessage->getPlainBody());
		$this->assertContains($mockedNotification->getParsedMessage(), $sentMessage->getPlainBody());
		$this->assertContains('http://test.server/oc', $sentMessage->getPlainBody());
	}

	public function testSendNotificationPrevented() {
		$mockedNotification = $this->getMockBuilder('\OCP\Notification\INotification')->disableOriginalConstructor()->getMock();
		$mockedNotification->method('getUser')->willReturn('userTest1');
		$mockedNotification->method('getObjectType')->willReturn('test_obj_type');
		$mockedNotification->method('getObjectId')->willReturn('202');
		$mockedNotification->method('getParsedSubject')->willReturn('This is a parsed subject');
		$mockedNotification->method('getParsedMessage')->willReturn('Parsed message is this');

		$this->manager->method('prepare')->willReturn($mockedNotification);
		$this->config->method('getUserValue')
			->will($this->returnValueMap([
				['userTest1', 'notificationsmail', 'email_sending_option', 'never', 'never']
		]));

		$sentMessage = $this->nsender->sendNotification($mockedNotification, 'http://test.server/oc', ['test@example.com']);
		$this->assertFalse($sentMessage);
	}

	public function willSendNotificationProvider() {
		$mockedAction = $this->getMockBuilder('\OCP\Notification\IAction')
			->disableOriginalConstructor()
			->getMock();
		$mockedNotification = $this->getMockBuilder('\OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$mockedNotification->method('getUser')->willReturn('userTest1');
		$mockedNotification->method('getObjectType')->willReturn('test_obj_type');
		$mockedNotification->method('getObjectId')->willReturn('202');
		$mockedNotification->method('getParsedSubject')->willReturn('This is a parsed subject');
		$mockedNotification->method('getParsedMessage')->willReturn('Parsed message is this');
		$mockedNotification->method('getActions')->willReturn([$mockedAction]);

		$mockedNotification2 = $this->getMockBuilder('\OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$mockedNotification2->method('getUser')->willReturn('userTest1');
		$mockedNotification2->method('getObjectType')->willReturn('test_obj_type');
		$mockedNotification2->method('getObjectId')->willReturn('202');
		$mockedNotification2->method('getParsedSubject')->willReturn('This is a parsed subject');
		$mockedNotification2->method('getParsedMessage')->willReturn('Parsed message is this');

		return [
			[$mockedNotification, 'never', false],
			[$mockedNotification, 'always', true],
			[$mockedNotification, 'action', true],
			[$mockedNotification, 'randomMissing', false],
			[$mockedNotification2, 'never', false],
			[$mockedNotification2, 'always', true],
			[$mockedNotification2, 'action', false],
			[$mockedNotification2, 'randomMissing', false],
		];
	}

	/**
	 * @dataProvider willSendNotificationProvider
	 */
	public function testWillSendNotification($notification, $configOption, $expectedValue) {
		$this->config->method('getUserValue')
			->will($this->returnValueMap([
				['userTest1', 'notificationsmail', 'email_sending_option', 'never', $configOption],
		]));
		$this->assertEquals($expectedValue, $this->nsender->willSendNotification($notification));
	}
}