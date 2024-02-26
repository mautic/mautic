<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\Exception\OwnerNotFoundException;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FromEmailHelperTest extends TestCase
{
    /** @var CoreParametersHelper&MockObject */
    private $coreParametersHelper;

    /** @var LeadRepository&MockObject */
    private $leadRepository;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->leadRepository       = $this->createMock(LeadRepository::class);
    }

    public function testOwnerIsReturnedWhenEmailEntityNotSet(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $defaultFrom = new AddressDTO('someone@somewhere.com', 'Someone');
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

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['user@somewhere.com' => 'First Last'], $fromEmail->getAddressArray());
    }

    public function testOwnerIsReturnedWhenEmailEntityIsSet(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $defaultFrom = new AddressDTO('someone@somewhere.com', 'Someone');
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

        $email = new Email();
        $email->setUseOwnerAsMailer(true);

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact, $email);

        $this->assertEquals(['user@somewhere.com' => 'First Last'], $fromEmail->getAddressArray());
    }

    public function testTokenizedEmailIsGivenPreference(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('{contactfield=other_email}', null);
        $contact     = ['other_email' => 'someone@somewhere.com'];

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => null], $fromEmail->getAddressArray());
    }

    public function testDefaultIsReturnedIfOwnerNotSet(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('someone@somewhere.com', null);
        $contact     = [];

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => null], $fromEmail->getAddressArray());
    }

    public function testDefaultIsReturnedWhenOwnerNotFound(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturn($this->returnValueMap(
                [
                    ['mailer_from_email', null, 'someone@somewhere.com'],
                    ['mailer_from_name', null, 'Someone'],
                    ['mailer_is_owner', null, true],
                ]
            ));

        $defaultFrom = new AddressDTO('someone@somewhere.com', 'Someone');
        $contact     = ['owner_id' => 1];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn(null);

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals($defaultFrom->getAddressArray(), $fromEmail->getAddressArray());
    }

    public function testTokenizedEmailIsReplacedWithOwnerWhenFieldEmptyAndDefaultNotOverriddenAndMailAsOwnerEnabled(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
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

        $defaultFrom = new AddressDTO('{contactfield=other_email}', null);
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['user@somewhere.com' => 'First Last'], $fromEmail->getAddressArray());
    }

    public function testTokenizedEmailIsReplacedWithSystemDefaultWhenFieldEmptyAndDefaultNotOverriddenAndMailAsOwnerDisabled(): void
    {
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
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

        $defaultFrom = new AddressDTO('{contactfield=other_email}', null);
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['default@somewhere.com' => 'Default'], $fromEmail->getAddressArray());
    }

    public function testTokenizedEmailIsReplacedWithOverriddenDefaultWhenFieldEmptyAndMailAsOwnerDisabled(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('mailer_is_owner')
            ->willReturn(false);

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('{contactfield=other_email}', null);
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $helper = $this->getHelper();
        $helper->setDefaultFrom(new AddressDTO('overridden@somewhere.com', null));
        $fromEmail = $helper->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals(['overridden@somewhere.com' => null], $fromEmail->getAddressArray());
    }

    public function testMultipleCallsReturnAppropriateEmail(): void
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $defaultFrom = new AddressDTO('someone@somewhere.com', 'Someone');

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
            $fromEmail = $helper->getFromAddressConsideringOwner($defaultFrom, $contact);
            $this->assertEquals([$users[$key]['email'] => 'First Last'], $fromEmail->getAddressArray());
        }
    }

    public function testTokenizedEmailIsReplacedWithContactField(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('{contactfield=other_email}');
        $contact     = ['other_email' => 'someone@somewhere.com'];

        $fromEmail = $this->getHelper()->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => null], $fromEmail->getAddressArray());
    }

    public function testTokenizedNameIsReplacedWithContactField(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('someone@somewhere.com', '{contactfield=other_name}');
        $contact     = [
            'other_name' => 'Thing One',
        ];

        $fromEmail = $this->getHelper()->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Thing One'], $fromEmail->getAddressArray());
    }

    public function testTokenizedFromIsReplacedWithContactField(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('{contactfield=other_email}', '{contactfield=other_name}');
        $contact     = [
            'other_email'=> 'thingone@somewhere.com',
            'other_name' => 'Thing One',
        ];

        $fromEmail = $this->getHelper()->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['thingone@somewhere.com' => 'Thing One'], $fromEmail->getAddressArray());
    }

    public function testTokenizedEmailIsReplacedWithSystemDefaultWhenFieldEmptyAndDefaultNotOverridden(): void
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
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

        $defaultFrom = new AddressDTO('{contactfield=other_email}', null);
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['default@somewhere.com' => 'Default'], $fromEmail->getAddressArray());
    }

    public function testTokenizedNameIsReplacedWithSystemDefaultWhenFieldEmptyAndDefaultNotOverridden(): void
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
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

        $defaultFrom = new AddressDTO('someone@somewhere.com', '{contactfield=other_name}');
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
            'other_name'  => '',
        ];

        $fromEmail = $this->getHelper()->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Default'], $fromEmail->getAddressArray());
    }

    public function testTokenizedEmailIsReplacedWithOverriddenDefaultWhenFieldEmpty(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('{contactfield=other_email}', null);
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
        ];

        $helper = $this->getHelper();
        $helper->setDefaultFrom(new AddressDTO('overridden@somewhere.com', null));
        $fromEmail = $helper->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['overridden@somewhere.com' => null], $fromEmail->getAddressArray());
    }

    public function testTokenizedNameIsReplacedWithOverriddenDefaultWhenFieldEmpty(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');
        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('someone@somewhere.com', '{contactfield=other_name}');
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
            'other_name'  => '',
        ];

        $helper = $this->getHelper();
        $helper->setDefaultFrom(new AddressDTO('overridden@somewhere.com', 'Thing Two'));
        $fromEmail = $helper->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Thing Two'], $fromEmail->getAddressArray());
    }

    public function testTokenizedNameIsReplacedWithSystemDefaultWhenFieldEmptyWithoutDefaultBeingOverriden(): void
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['mailer_from_email'], ['mailer_from_name'])
            ->willReturnOnConsecutiveCalls('default@somewhere.com', 'Default Name');

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('someone@somewhere.com', '{contactfield=other_name}');
        $contact     = [
            'owner_id'    => 1,
            'other_email' => '',
            'other_name'  => '',
        ];

        $helper = $this->getHelper();
        $from   = $helper->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals(['someone@somewhere.com' => 'Default Name'], $from->getAddressArray());
    }

    public function testNullContactReturnsDefaultAddress(): void
    {
        $this->coreParametersHelper->expects($this->never())
            ->method('get');
        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('default@somewhere.com', 'Default Name');
        $contact     = null;
        $helper      = $this->getHelper();
        $helper->setDefaultFrom(new AddressDTO('overridden@somewhere.com', null));
        $from = $helper->getFromAddressConsideringOwner($defaultFrom, $contact);

        $this->assertEquals('default@somewhere.com', $from->getEmail());
        $this->assertEquals('Default Name', $from->getName());
    }

    public function testNullContactReturnsDefaultAddressWhenMailerIsOwnerEnabled(): void
    {
        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $defaultFrom = new AddressDTO('default@somewhere.com', 'Default Name');
        $contact     = null;
        $helper      = $this->getHelper();
        $helper->setDefaultFrom(new AddressDTO('overridden@somewhere.com', null));
        $from = $helper->getFromAddressDto($defaultFrom, $contact);

        $this->assertEquals('default@somewhere.com', $from->getEmail());
        $this->assertEquals('Default Name', $from->getName());
    }

    public function testContactOwnerIsReturnedWhenMailAsOwnerIsEnabled(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
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

    public function testExceptionIsThrownWhenMailAsOwnerIsDisabled(): void
    {
        $this->expectException(OwnerNotFoundException::class);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('mailer_is_owner')
            ->willReturn(false);

        $this->leadRepository->expects($this->never())
            ->method('getLeadOwner');

        $owner = $this->getHelper()->getContactOwner(1);

        $this->assertEquals(null, $owner);
    }

    public function testExceptionIsThrownWhenOwnerNotFound(): void
    {
        $this->expectException(OwnerNotFoundException::class);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('mailer_is_owner')
            ->willReturn(true);

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn(null);

        $owner = $this->getHelper()->getContactOwner(1);

        $this->assertEquals(null, $owner);
    }

    public function testSignatureOfLastFetchedOwnerReturned(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
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
        $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $this->assertEquals($user['signature'], $helper->getSignature());
    }

    public function testSignatureHasUserTokensReplaces(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
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
        $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $this->assertEquals('user@somewhere.com First there', $helper->getSignature());
    }

    public function testEmptySignatureIsReturnedWhenOwnerIsReset(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
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
        $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $helper->getFromAddressDto(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $this->assertEquals('', $helper->getSignature());
    }

    public function testEmptySignatureIsReturnedWhenOwnerIsNotFound(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturn($this->returnValueMap(
                [
                    ['mailer_from_email', null, 'someone@somewhere.com'],
                    ['mailer_from_name', null, 'Someone'],
                    ['mailer_is_owner', null, true],
                ]
            ));

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn(null);

        $helper = $this->getHelper();
        $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $this->assertEquals('', $helper->getSignature());
    }

    public function testSignatureIsReturnedForAppropriateUser(): void
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
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
        $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 2]
        );

        $this->assertEquals('user 2', $helper->getSignature());
    }

    public function testOwnerWithEncodedCharactersInName(): void
    {
        $params = [
            ['mailer_is_owner', null, true],
        ];
        $this->coreParametersHelper->method('get')->will($this->returnValueMap($params));

        $user = [
            'id'         => 1,
            'first_name' => 'First',
            'last_name'  => 'No Body&#39;s Business',
            'email'      => 'user@somewhere.com',
            'signature'  => '|USER_EMAIL| |USER_FIRST_NAME| there',
        ];

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn($user);

        $helper = $this->getHelper();
        $from   = $helper->getFromAddressConsideringOwner(
            new AddressDTO('someone@somewhere.com', null),
            ['owner_id' => 1]
        );

        $this->assertEquals(['user@somewhere.com' => "First No Body's Business"], $from->getAddressArray());
    }

    private function getHelper(): FromEmailHelper
    {
        return new FromEmailHelper($this->coreParametersHelper, $this->leadRepository);
    }
}
