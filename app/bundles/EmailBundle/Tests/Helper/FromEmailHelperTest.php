<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Helper\Exception\OwnerNotFoundException;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\LeadBundle\Entity\LeadRepository;

class FromEmailHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParametersHelper;

    /**
     * @var LeadRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $leadRepository;

    protected function setUp()
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->leadRepository       = $this->createMock(LeadRepository::class);
    }

    public function testOwnerIsReturned()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $defaultFrom = ['someone@somewhere.com' => 'Someone'];
        $contact     = ['owner_id' => 1];

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => 'hello there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $fromEmail = $this->getHelper()->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['user@somewhere.com' => 'First Last'], $fromEmail);
    }

    public function testTokenizedEmailIsGivenPreference()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = ['other_email' => 'someone@somewhere.com'];

        $fromEmail = $this->getHelper()->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => null], $fromEmail);
    }

    public function testDefaultIsReturnedIfOwnerNotSet()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['someone@somewhere.com' => null];
        $contact     = [];

        $fromEmail = $this->getHelper()->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => null], $fromEmail);
    }

    public function testDefaultIsReturnedWhenOwnerNotFound()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $defaultFrom = ['someone@somewhere.com' => 'Someone'];
        $contact     = ['owner_id' => 1];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn(null);

        $fromEmail = $this->getHelper()->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals($defaultFrom, $fromEmail);
    }

    public function testTokenizedEmailIsReplacedWithOwnerWhenFieldEmptyAndDefaultNotOverriddenAndMailAsOwnerEnabled()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => 'hello there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['user@somewhere.com' => 'First Last'], $fromEmail);
    }

    public function testTokenizedEmailIsReplacedWithSystemDefaultWhenFieldEmptyAndDefaultNotOverriddenAndMailAsOwnerDisabled()
    {
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('getParameter')
            ->withConsecutive(
                ['mailer_is_owner'],
                ['mailer_from_email'],
                ['mailer_from_name']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                'default@somewhere.com',
                'Default'
            );

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['default@somewhere.com' => 'Default'], $fromEmail);
    }

    public function testTokenizedEmailIsReplacedWithOverriddenDefaultWhenFieldEmptyAndMailAsOwnerDisabled()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(false);

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $helper = $this->getHelper();
        $helper->setDefaultFromArray(['overridden@somewhere.com' => null]);
        $fromEmail = $helper->getFromAddressArrayConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['overridden@somewhere.com' => null], $fromEmail);
    }

    public function testMultipleCallsReturnAppropriateEmail()
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $defaultFrom = ['someone@somewhere.com' => 'Someone'];

        $contacts = [
            ['owner_id' => 1],
            ['owner_id' => 2],
        ];

        $users = [
            [
                'id'         => 1,
                'first_name' => 'First',
                'last_name'  => 'Last',
                'email'      => 'user@somewhere.com',
                'signature'  => 'hello there',
            ],
            [
                'id'         => 3,
                'first_name' => 'First',
                'last_name'  => 'Last',
                'email'      => 'user2@somewhere.com',
                'signature'  => 'hello there again',
            ],
        ];

        $this->leadRepository->expects($this->exactly(2))
            ->method('getLeadOwner')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($users[0], $users[1]);

        $helper = $this->getHelper();
        foreach ($contacts as $key => $contact) {
            $fromEmail = $helper->getFromAddressArrayConsideringOwner($defaultFrom, $contact);
            $this->assertEquals([$users[$key]['email'] => 'First Last'], $fromEmail);
        }
    }

    public function testTokenizedEmailIsReplacedWithContactField()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = ['other_email' => 'someone@somewhere.com'];

        $fromEmail = $this->getHelper()->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => null], $fromEmail);
    }

    public function testTokenizedNameIsReplacedWithContactField()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['someone@somewhere.com' => '{contactfield=other_name}'];
        $contact     = [
            'other_name' => 'Thing One',
        ];

        $fromEmail = $this->getHelper()->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Thing One'], $fromEmail);
    }

    public function testTokenizedFromIsReplacedWithContactField()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => '{contactfield=other_name}'];
        $contact     = [
            'other_email'=> 'thingone@somewhere.com',
            'other_name' => 'Thing One',
        ];

        $fromEmail = $this->getHelper()->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['thingone@somewhere.com' => 'Thing One'], $fromEmail);
    }

    public function testTokenizedEmailIsReplacedWithSystemDefaultWhenFieldEmptyAndDefaultNotOverridden()
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(
                ['mailer_from_email'],
                ['mailer_from_name']
            )
            ->willReturnOnConsecutiveCalls(
                'default@somewhere.com',
                'Default'
            );

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['default@somewhere.com' => 'Default'], $fromEmail);
    }

    public function testTokenizedNameIsReplacedWithSystemDefaultWhenFieldEmptyAndDefaultNotOverridden()
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(
                ['mailer_from_email'],
                ['mailer_from_name']
            )
            ->willReturnOnConsecutiveCalls(
                'default@somewhere.com',
                'Default'
            );

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['someone@somewhere.com' => '{contactfield=other_name}'];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
            'other_name'  => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Default'], $fromEmail);
    }

    public function testTokenizedEmailIsReplacedWithOverriddenDefaultWhenFieldEmpty()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['{contactfield=other_email}' => null];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $helper = $this->getHelper();
        $helper->setDefaultFromArray(['overridden@somewhere.com' => null]);
        $fromEmail = $helper->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['overridden@somewhere.com' => null], $fromEmail);
    }

    public function testTokenizedNameIsReplacedWithOverriddenDefaultWhenFieldEmpty()
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = ['someone@somewhere.com' => '{contactfield=other_name}'];
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
            'other_name'  => '',
        ];

        $helper = $this->getHelper();
        $helper->setDefaultFromArray(['overridden@somewhere.com' => 'Thing Two']);
        $fromEmail = $helper->getFromAddressArray($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Thing Two'], $fromEmail);
    }

    public function testContactOwnerIsReturnedWhenMailAsOwnerIsEnabled()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => 'hello there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $owner = $this->getHelper()->getContactOwner(1);

        $this->assertTrue($user === $owner);
    }

    public function testExceptionIsThrownWhenMailAsOwnerIsDisabled()
    {
        $this->expectException(OwnerNotFoundException::class);

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(false);

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $owner = $this->getHelper()->getContactOwner(1);

        $this->assertEquals(null, $owner);
    }

    public function testExceptionIsThrownWhenOwnerNotFound()
    {
        $this->expectException(OwnerNotFoundException::class);

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn(null);

        $owner = $this->getHelper()->getContactOwner(1);

        $this->assertEquals(null, $owner);
    }

    public function testSignatureOfLastFetchedOwnerReturned()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => 'hello there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $helper = $this->getHelper();
        $helper->getFromAddressArrayConsideringOwner(
            ['someone@somewhere.com' => null],
            ['owner_id' => 1]
        );

        $this->assertEquals($user['signature'], $helper->getSignature());
    }

    public function testSignatureHasUserTokensReplaces()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => '|USER_EMAIL| |USER_FIRST_NAME| there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $helper = $this->getHelper();
        $helper->getFromAddressArrayConsideringOwner(
            ['someone@somewhere.com' => null],
            ['owner_id' => 1]
        );

        $this->assertEquals('user@somewhere.com First there', $helper->getSignature());
    }

    public function testEmptySignatureIsReturnedWhenOwnerIsReset()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => '|USER_EMAIL| |USER_FIRST_NAME| there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $helper = $this->getHelper();
        $helper->getFromAddressArrayConsideringOwner(
            ['someone@somewhere.com' => null],
            ['owner_id' => 1]
        );

        $helper->getFromAddressArray(
            ['someone@somewhere.com' => null],
            ['owner_id' => 1]
        );

        $this->assertEquals('', $helper->getSignature());
    }

    public function testEmptySignatureIsReturnedWhenOwnerIsNotFound()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn(null);

        $helper = $this->getHelper();
        $helper->getFromAddressArrayConsideringOwner(
            ['someone@somewhere.com' => null],
            ['owner_id' => 1]
        );

        $this->assertEquals('', $helper->getSignature());
    }

    public function testSignatureIsReturnedForAppropriateUser()
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user@somewhere.com',
            'signature'  => 'user 1',
        ];

        $user2 = [
            'id'         => 2,
            'first_name' => 'First',
            'last_name'  => 'Last',
            'email'      => 'user2@somewhere.com',
            'signature'  => 'user 2',
        ];

        $this->leadRepository->expects($this->exactly(2))
            ->method('getLeadOwner')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($user, $user2);

        $helper = $this->getHelper();
        $helper->getFromAddressArrayConsideringOwner(
            ['someone@somewhere.com' => null],
            ['owner_id' => 1]
        );

        $helper->getFromAddressArrayConsideringOwner(
            ['someone@somewhere.com' => null],
            ['owner_id' => 2]
        );

        $this->assertEquals('user 2', $helper->getSignature());
    }

    /**
     * @return FromEmailHelper
     */
    private function getHelper()
    {
        return new FromEmailHelper($this->coreParametersHelper, $this->leadRepository);
    }
}
