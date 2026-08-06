<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        if (strpos($this->name(), 'WithSaml') > 0) {
            $this->configParams['saml_idp_metadata'] = 'any_string';
        }

        parent::setUp();
        $this->logoutUser();
    }

    public function testLoginRetryPageShowsErrorWithSaml(): void
    {
        $this->client->request(Request::METHOD_GET, '/saml/login_retry');

        $clientResponse = $this->client->getResponse();

        $this->assertResponseIsSuccessful();

        $validationError = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.user.security.saml.clearsession', [], 'flashes');
        $this->assertStringContainsString($validationError, (string) $clientResponse->getContent());
    }

    public function testLoginRetryPageRedirectsToLoginWithoutSaml(): void
    {
        $this->client->request(Request::METHOD_GET, '/saml/login_retry');

        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $validationError = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.user.security.saml.clearsession', [], 'flashes');
        $this->assertStringNotContainsString($validationError, (string) $clientResponse->getContent());

        $loginText = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.user.auth.form.loginbtn', [], 'messages');
        $this->assertStringContainsString($loginText, (string) $clientResponse->getContent());
    }

    public function testLoginPageDoesNotLoadEditorAssets(): void
    {
        $this->client->request(Request::METHOD_GET, '/login');

        $this->assertResponseIsSuccessful();

        $clientResponse = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('ckeditor.js', $clientResponse);
    }
}
