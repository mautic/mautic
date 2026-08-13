<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\HubspotApi;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HubspotApiTest extends TestCase
{
    private function createEmailValidator(): EmailValidator
    {
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        return new EmailValidator($this->createStub(TranslatorInterface::class), $dispatcher);
    }

    #[TestDox('Test Hubspot api when the api-key is invalid')]
    public function testHubspotWhenKeyIsInvalid(): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $message     = 'The API key provided is invalid. View or manage your API key here: https://app-eu1.hubspot.com/l/api-key/';
        $code        = 401;
        $response    = [
            'status'        => 'error',
            'message'       => $message,
            'correlationId' => '00000000-0000-0000-0000-000000000000',
            'category'      => 'INVALID_AUTHENTICATION',
            'links'         => [
                'api key' => 'https://app-eu1.hubspot.com/l/api-key/',
            ],
        ];

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturn(
                [
                    'error' => [
                        'code'    => $code,
                        'message' => json_encode($response),
                    ],
                ]
            );
        $integration->expects($this->once())
            ->method('getAuthenticationType')
            ->willReturn('crm');

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);

        $api = new HubspotApi($integration, $this->createEmailValidator());
        $api->getLeadFields();

        self::fail('ApiErrorException not thrown');
    }

    public function testHubspotWhenKeyIsInvalidIfOauth(): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $message     = 'The API key provided is invalid. View or manage your API key here: https://app-eu1.hubspot.com/l/api-key/';
        $response    = [
            'error'         => 'error',
            'code'          => 402,
            'message'       => $message,
            'correlationId' => '00000000-0000-0000-0000-000000000000',
            'category'      => 'INVALID_AUTHENTICATION',
            'links'         => [
                'api key' => 'https://app-eu1.hubspot.com/l/api-key/',
            ],
        ];

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturn(['error' => $response]);
        $integration->expects($this->once())
            ->method('getAuthenticationType')
            ->willReturn('oauth2');

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode(0);

        $api = new HubspotApi($integration, $this->createEmailValidator());
        $api->getLeadFields();

        self::fail('ApiErrorException not thrown');
    }

    #[DataProvider('provideInvalidEmails')]
    public function testCreateLeadRejectsInvalidEmail(string $email): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $integration->expects($this->never())->method('formatLeadDataForCreateOrUpdate');

        $this->expectException(InvalidEmailException::class);

        $api = new HubspotApi($integration, $this->createEmailValidator());
        $api->createLead(['email' => $email], new Lead());
    }

    /**
     * @return \Iterator<array{string}>
     */
    public static function provideInvalidEmails(): \Iterator
    {
        yield ['john@doe'];
        yield ['jo hn@doe.email'];
        yield ['jo^hn@doe.email'];
        yield ['jo;hn@doe.email'];
        yield ['jo&hn@doe.email'];
        yield ['jo*hn@doe.email'];
        yield ['jo%hn@doe.email'];
    }

    #[DataProvider('provideValidEmails')]
    public function testCreateLeadAcceptsValidEmail(string $email): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $integration->expects($this->once())
            ->method('formatLeadDataForCreateOrUpdate')
            ->willReturn([]);

        $api = new HubspotApi($integration, $this->createEmailValidator());

        $this->assertSame([], $api->createLead(['email' => $email], new Lead()));
    }

    /**
     * @return \Iterator<array{string}>
     */
    public static function provideValidEmails(): \Iterator
    {
        yield ['john@doe.com'];
        yield ['john@doe.email'];
        yield ['john.doe@email.com'];
        yield ['john+doe@email.com'];
        yield ["jo'hn@doe.email"];
        yield ['john@doe.whatevertldtheycomewithinthefuture'];
    }
}
