<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

class SecurityControllerTest extends MauticMysqlTestCase
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

        $this->assertEquals(200, $clientResponse->getStatusCode());

        $validationError = self::getContainer()->get('translator')->trans('mautic.user.security.saml.clearsession', [], 'flashes');
        $this->assertStringContainsString($validationError, $clientResponse->getContent());
    }

    public function testLoginRetryPageRedirectsToLoginWithoutSaml(): void
    {
        $this->client->request(Request::METHOD_GET, '/saml/login_retry');

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());

        $validationError = self::getContainer()->get('translator')->trans('mautic.user.security.saml.clearsession', [], 'flashes');
        $this->assertStringNotContainsString($validationError, $clientResponse->getContent());

        $loginText = self::getContainer()->get('translator')->trans('mautic.user.auth.form.loginbtn', [], 'messages');
        $this->assertStringContainsString($loginText, $clientResponse->getContent());
    }
}
