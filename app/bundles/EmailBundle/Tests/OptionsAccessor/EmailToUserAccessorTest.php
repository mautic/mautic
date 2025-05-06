<?php

namespace Mautic\EmailBundle\Tests\OptionsAccessor;

use Mautic\EmailBundle\OptionsAccessor\EmailToUserAccessor;
use Mautic\UserBundle\Entity\User;

class EmailToUserAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testTransformToUserIds(): void
    {
        $config            = [];
        $config['user_id'] = [4, 6];

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = [
            ['id' => 4],
            ['id' => 6],
        ];

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend());
    }

    public function testTransformToUserIdsWithOwnerEntityButNoOwnerSetting(): void
    {
        $config            = [];
        $config['user_id'] = [4, 6];

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = [
            ['id' => 4],
            ['id' => 6],
        ];

        $mockOwner = $this->createMock(User::class);

        $mockOwner->expects($this->never()) // $config['to_owner'] is not set
            ->method('getId')
            ->willReturn(5);

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend($mockOwner));
    }

    public function testTransformToUserIdsWithDifferentOwnerId(): void
    {
        $config             = [];
        $config['user_id']  = [4, 6];
        $config['to_owner'] = true;

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = [
            ['id' => 4],
            ['id' => 6],
            ['id' => 5],
        ];

        $mockOwner = $this->createMock(User::class);

        $mockOwner->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(5);

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend($mockOwner));
    }

    public function testTransformToUserIdsWithSameOwnerId(): void
    {
        $config             = [];
        $config['user_id']  = [4, 6];
        $config['to_owner'] = true;

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = [
            ['id' => 4],
            ['id' => 6],
        ];

        $mockOwner = $this->createMock(User::class);

        $mockOwner->expects($this->once())
            ->method('getId')
            ->willReturn(6);

        $this->assertEquals($expected, $emailToUserAccessor->getUserIdsToSend($mockOwner));
    }

    public function testFormatToAddressOneEmail(): void
    {
        $config       = [];
        $config['to'] = 'john@doe.com';

        $emailToUserAccessor = new EmailToUserAccessor($config);

        $expected = ['john@doe.com'];

        $this->assertEquals($expected, $emailToUserAccessor->getToFormatted());
    }

    public function testFormatToAddressMoreEmails(): void
    {
        $config       = [];
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
