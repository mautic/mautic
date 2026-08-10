<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
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

        $emailValidatorMock = $this->createStub(EmailValidator::class);

        $api = new HubspotApi($emailValidatorMock, $integration);
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

        $api = new HubspotApi($this->createStub(EmailValidator::class), $integration);
        $api->getLeadFields();

        self::fail('ApiErrorException not thrown');
    }

    /**
     * @return \Iterator<array{string, string}>
     */
    public static function provideInvalidEmails(): \Iterator
    {
        yield ['john@doe', 'Email address [john@doe] is invalid'];
        yield ['jo hn@doe.email', 'Email address [jo hn@doe.email] is invalid'];
        yield ['jo^hn@doe.email', 'Email address [jo^hn@doe.email] contains this invalid character: ^'];
        yield ['jo\'hn@doe.email', 'Email address [jo\'hn@doe.email] contains this invalid character: \''];
        yield ['jo&hn@doe.email', 'Email address [jo&hn@doe.email] contains this invalid character: &'];
        yield ['jo*hn@doe.email', 'Email address [jo*hn@doe.email] contains this invalid character: *'];
        yield ['jo%hn@doe.email', 'Email address [jo%hn@doe.email] contains this invalid character: %'];
    }

    #[DataProvider('provideInvalidEmails')]
    public function testCreateLeadRejectsInvalidEmail(string $email, string $expectedMessage): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $integration->expects($this->never())
            ->method('makeRequest');

        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage($expectedMessage);

        $emailValidator = new EmailValidator(
            $this->createStub(TranslatorInterface::class),
            $this->createStub(EventDispatcherInterface::class)
        );

        $api = new HubspotApi($emailValidator, $integration);
        $api->createLead(['email' => $email], null);
    }
}
