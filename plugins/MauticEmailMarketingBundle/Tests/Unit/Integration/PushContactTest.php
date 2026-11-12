<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Integration;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PushContactTest extends TestCase
{
    private const array KEYS = ['password' => 'key-us7'];

    public function testMailchimpSubscribesTheMappedContact(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->once())
            ->method('subscribe')
            ->with(self::KEYS, 'a1', 'john@example.com', ['FNAME' => 'John'], false, true);

        $mapper      = $this->mapper(['EMAIL' => 'john@example.com', 'FNAME' => 'John'], ['EMAIL' => 'email', 'FNAME' => 'firstname']);
        $integration = $this->configure(new MailchimpIntegration($client, $mapper, $this->translator(), $this->cache()));

        $this->assertSame(MailchimpType::class, $integration->getPushContactConfigFormName());
        $integration->pushContact(new Lead(), ['list_settings' => ['list' => 'a1', 'doubleOptin' => '0', 'sendWelcome' => '1', 'leadFields' => ['EMAIL' => 'email', 'FNAME' => 'firstname']]]);
    }

    public function testMailchimpSendsUnknownForEmptyRequiredMergeFields(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->once())
            ->method('getMergeFields')
            ->with(self::KEYS, 'a1')
            ->willReturn([
                'FNAME' => ['label' => 'First Name', 'required' => true],
                'LNAME' => ['label' => 'Last Name', 'required' => false],
                'EMAIL' => ['label' => 'Email', 'required' => true],
            ]);
        $client->expects($this->exactly(2))
            ->method('subscribe')
            ->with(self::KEYS, 'a1', 'john@example.com', ['FNAME' => 'mautic.integration.form.lead.unknown'], true, true);

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['EMAIL' => 'john@example.com']), $this->translator(), $this->cache()));

        // The second push reads the merge fields from the cache
        $config = ['list_settings' => ['list' => 'a1', 'leadFields' => ['EMAIL' => 'email', 'FNAME' => 'firstname', 'LNAME' => 'lastname']]];
        $integration->pushContact(new Lead(), $config);
        $integration->pushContact(new Lead(), $config);
    }

    public function testMailchimpReportsApiErrors(): void
    {
        $client = $this->createStub(MailchimpClient::class);
        $client->method('subscribe')->willThrowException(new ApiException('Member Exists'));

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['EMAIL' => 'john@example.com']), $this->translator(), $this->cache()));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('Member Exists');

        $integration->pushContact(new Lead(), ['list_settings' => ['list' => 'a1', 'leadFields' => ['EMAIL' => 'email']]]);
    }

    public function testMailchimpNeedsAList(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->never())->method('subscribe');

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['EMAIL' => 'john@example.com']), $this->translator(), $this->cache()));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('mautic.emailmarketing.push.no_list MailChimp');

        $integration->pushContact(new Lead(), ['list_settings' => ['list' => '']]);
    }

    public function testMailchimpNeedsTheEmail(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->expects($this->never())->method('subscribe');

        $integration = $this->configure(new MailchimpIntegration($client, $this->mapper(['FNAME' => 'John']), $this->translator(), $this->cache()));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('mautic.emailmarketing.push.no_email MailChimp');

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

        $mapping     = ['email' => 'email', 'first_name' => 'firstname', 'address_city' => 'city', 'customfield_1' => 'color'];
        $integration = $this->configure(new ConstantContactIntegration($client, $this->mapper([
            'email'         => 'john@example.com',
            'first_name'    => 'John',
            'address_city'  => 'address_x',
            'customfield_1' => 'customfield_y',
        ], $mapping), $this->translator()));

        $this->assertSame(ConstantContactType::class, $integration->getPushContactConfigFormName());
        $integration->pushContact(new Lead(), ['list_settings' => ['list' => '5', 'sendWelcome' => '0', 'leadFields' => $mapping]]);
    }

    public function testIcontactSendsCustomFieldsByTheirId(): void
    {
        $client = $this->createMock(IcontactClient::class);
        $client->expects($this->once())
            ->method('subscribe')
            ->with(self::KEYS, '7', ['email' => 'john@example.com', 'firstName' => 'John', 'color' => 'blue']);

        $mapping     = ['email' => 'email', 'cf_color' => 'color', 'firstName' => 'firstname'];
        $integration = $this->configure(new IcontactIntegration($client, $this->mapper([
            'email'     => 'john@example.com',
            'cf_color'  => 'blue',
            'firstName' => 'John',
        ], $mapping), $this->translator()));

        $this->assertSame(IcontactType::class, $integration->getPushContactConfigFormName());
        $integration->pushContact(new Lead(), ['list_settings' => ['list' => '7', 'leadFields' => $mapping]]);
    }

    /**
     * @param class-string<MailchimpClient|ConstantContactClient|IcontactClient>                $clientClass
     * @param array<string, string>                                                          $data
     * @param array<string, mixed>                                                           $config
     */
    #[DataProvider('failureProvider')]
    public function testFailures(string $clientClass, array $data, array $config, string $message): void
    {
        $client = $this->createStub($clientClass);
        $client->method('subscribe')->willThrowException(new ApiException('Rejected'));

        $integration = $this->configure(match (true) {
            $client instanceof MailchimpClient       => new MailchimpIntegration($client, $this->mapper($data), $this->translator(), $this->cache()),
            $client instanceof ConstantContactClient => new ConstantContactIntegration($client, $this->mapper($data), $this->translator()),
            $client instanceof IcontactClient        => new IcontactIntegration($client, $this->mapper($data), $this->translator()),
        });

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage($message);

        $integration->pushContact(new Lead(), $config);
    }

    /**
     * @return iterable<string, array{class-string, array<string, string>, array<string, mixed>, string}>
     */
    public static function failureProvider(): iterable
    {
        $integrations = [
            'Mailchimp'        => [MailchimpClient::class, 'EMAIL', 'MailChimp'],
            'Constant Contact' => [ConstantContactClient::class, 'email', 'Constant Contact'],
            'iContact'         => [IcontactClient::class, 'email', 'iContact'],
        ];

        foreach ($integrations as $label => [$clientClass, $emailField, $displayName]) {
            yield $label.': no list settings' => [$clientClass, [$emailField => 'john@example.com'], [], 'mautic.emailmarketing.push.no_list '.$displayName];
            yield $label.': no email' => [$clientClass, [], ['list_settings' => ['list' => '1']], 'mautic.emailmarketing.push.no_email '.$displayName];
            yield $label.': rejected' => [$clientClass, [$emailField => 'john@example.com'], ['list_settings' => ['list' => '1']], 'Rejected'];
        }
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id, array $parameters = []): string => trim($id.' '.implode(' ', $parameters)));

        return $translator;
    }

    private function cache(): CacheProviderInterface
    {
        $adapter = new ArrayAdapter();
        $cache   = $this->createStub(CacheProviderInterface::class);
        $cache->method('getItem')->willReturnCallback($adapter->getItem(...));
        $cache->method('save')->willReturnCallback($adapter->save(...));

        return $cache;
    }

    /**
     * @param array<string, string>      $data
     * @param array<string, string>|null $mapping the mapping the integration must pass; null accepts any
     */
    private function mapper(array $data, ?array $mapping = null): ContactFieldMapper
    {
        if (null === $mapping) {
            $mapper = $this->createStub(ContactFieldMapper::class);
            $mapper->method('map')->willReturn($data);

            return $mapper;
        }

        $mapper = $this->createMock(ContactFieldMapper::class);
        $mapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf(Lead::class), $mapping)
            ->willReturn($data);

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
