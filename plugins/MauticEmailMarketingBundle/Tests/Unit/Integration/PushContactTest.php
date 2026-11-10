<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Integration;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEmailMarketingBundle\Client\ConstantContactClient;
use MauticPlugin\MauticEmailMarketingBundle\Client\IcontactClient;
use MauticPlugin\MauticEmailMarketingBundle\Client\MailchimpClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactType;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactType;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\MailchimpType;
use MauticPlugin\MauticEmailMarketingBundle\Helper\ContactFieldMapper;
use MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration;
use MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration;
use MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration;
use PHPUnit\Framework\TestCase;

final class PushContactTest extends TestCase
{
    private const array KEYS = ['password' => 'key-us7'];

    public function testMailchimpSubscribesTheMappedContact(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->once())
            ->method('subscribe')
            ->with(self::KEYS, 'a1', 'john@example.com', ['FNAME' => 'John'], false, true);

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['EMAIL' => 'john@example.com', 'FNAME' => 'John'])));

        $this->assertSame(MailchimpType::class, $integration->getPushContactConfigFormName());
        $integration->pushContact(new Lead(), ['list_settings' => ['list' => 'a1', 'doubleOptin' => '0', 'sendWelcome' => '1', 'leadFields' => ['EMAIL' => 'email', 'FNAME' => 'firstname']]]);
    }

    public function testMailchimpReportsApiErrors(): void
    {
        $client = $this->createStub(MailchimpClient::class);
        $client->method('subscribe')->willThrowException(new ApiException('Member Exists'));

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['EMAIL' => 'john@example.com'])));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('Member Exists');

        $integration->pushContact(new Lead(), ['list_settings' => ['list' => 'a1', 'leadFields' => ['EMAIL' => 'email']]]);
    }

    public function testMailchimpNeedsAList(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->never())->method('subscribe');

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['EMAIL' => 'john@example.com'])));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('No Mailchimp list is selected.');

        $integration->pushContact(new Lead(), ['list_settings' => ['list' => '']]);
    }

    public function testMailchimpNeedsTheEmail(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->never())->method('subscribe');

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['FNAME' => 'John'])));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('The contact has no value for the field mapped to the Mailchimp email.');

        $integration->pushContact(new Lead(), ['list_settings' => ['list' => 'a1']]);
    }

    public function testConstantContactGroupsAddressAndCustomFieldsLikeTheFormerIntegration(): void
    {
        $client = $this->createMock(ConstantContactClient::class);
        $client->expects($this->once())
            ->method('subscribe')
            ->with(self::KEYS, '5', 'john@example.com', [
                'first_name'    => 'John',
                'addresses'     => ['city' => 'address_x', 'address_type' => 'PERSONAL'],
                'custom_fields' => [['name' => 'CustomField1', 'value' => 'customfield_y']],
            ], 'ACTION_BY_OWNER');

        $integration = $this->configure(new ConstantContactIntegration($client, $this->mapper([
            'email'         => 'john@example.com',
            'first_name'    => 'John',
            'address_city'  => 'address_x',
            'customfield_1' => 'customfield_y',
        ])));

        $this->assertSame(ConstantContactType::class, $integration->getPushContactConfigFormName());
        $integration->pushContact(new Lead(), ['list_settings' => ['list' => '5', 'sendWelcome' => '0']]);
    }

    public function testIcontactSendsCustomFieldsByTheirId(): void
    {
        $client = $this->createMock(IcontactClient::class);
        $client->expects($this->once())
            ->method('subscribe')
            ->with(self::KEYS, '7', ['email' => 'john@example.com', 'firstName' => 'John', 'color' => 'blue']);

        $integration = $this->configure(new IcontactIntegration($client, $this->mapper([
            'email'     => 'john@example.com',
            'cf_color'  => 'blue',
            'firstName' => 'John',
        ])));

        $this->assertSame(IcontactType::class, $integration->getPushContactConfigFormName());
        $integration->pushContact(new Lead(), ['list_settings' => ['list' => '7']]);
    }

    /**
     * @param array<string, string> $data
     */
    private function mapper(array $data): ContactFieldMapper
    {
        $mapper = $this->createStub(ContactFieldMapper::class);
        $mapper->method('map')->willReturn($data);

        return $mapper;
    }

    /**
     * @template T of MailchimpIntegration|ConstantContactIntegration|IcontactIntegration
     *
     * @param T $integration
     *
     * @return T
     */
    private function configure(MailchimpIntegration|ConstantContactIntegration|IcontactIntegration $integration): MailchimpIntegration|ConstantContactIntegration|IcontactIntegration
    {
        $configuration = new Integration();
        $configuration->setApiKeys(self::KEYS);
        $integration->setIntegrationConfiguration($configuration);

        return $integration;
    }
}
