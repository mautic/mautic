<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Test\OptionsAccessor;

use Mautic\EmailBundle\OptionsAccessor\EmailToUserAccessor;
use Mautic\UserBundle\Entity\User;

class EmailToUserAccessorTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformToUserIds()
    {
        $config = [];
        $config['user_id'] = [4, 6];

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected   = [
            ['id' => 4],
            ['id' => 6],
        ];

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend());
    }

    public function testTransformToUserIdsWithOwnerEntityButNoOwnerSetting()
    {
        $config = [];
        $config['user_id'] = [4, 6];

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected   = [
            ['id' => 4],
            ['id' => 6],
        ];

        $mockOwner = $this->getMockBuilder(User::class)
            ->getMock();

        $mockOwner->expects($this->never()) //$config['to_owner'] is not set
            ->method('getId')
            ->will($this->returnValue(5));

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend($mockOwner));
    }

    public function testTransformToUserIdsWithDifferentOwnerId()
    {
        $config = [];
        $config['user_id'] = [4, 6];
        $config['to_owner'] = true;

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected   = [
            ['id' => 4],
            ['id' => 6],
            ['id' => 5],
        ];

        $mockOwner = $this->getMockBuilder(User::class)
            ->getMock();

        $mockOwner->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(5));

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend($mockOwner));
    }

    public function testTransformToUserIdsWithSameOwnerId()
    {
        $config = [];
        $config['user_id'] = [4, 6];
        $config['to_owner'] = true;

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected   = [
            ['id' => 4],
            ['id' => 6],
        ];

        $mockOwner = $this->getMockBuilder(User::class)
            ->getMock();

        $mockOwner->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(6));

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend($mockOwner));
    }

    public function testFormatToAddressOneEmail()
    {
        $config = [];
        $config['to'] = 'john@doe.com';

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = ['john@doe.com'];

        $this->assertEquals($expected, $emailToUserAccessor->getToFormatted());
    }

    public function testFormatToAddressMoreEmails()
    {
        $config = [];
        $config['to'] = 'john@doe.com, peter@doe.com,doe@mark.com';

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = [
            'john@doe.com',
            'peter@doe.com',
            'doe@mark.com',
        ];

        $this->assertEquals($expected, $emailToUserAccessor->getToFormatted());
    }
}
