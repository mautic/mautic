<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

class PublicControllerTest extends MauticMysqlTestCase
{
    private const PASSWORD_RESET_URI = '/passwordreset';

    protected function setUp(): void
    {
        if (strpos($this->name(), 'WithSaml') > 0) {
            $this->configParams['saml_idp_metadata'] = 'any_string';
        }
        parent::setUp();
    }

    /**
     * Tests to ensure that xss is prevented on password reset page.
     */
    public function testXssFilterOnPasswordReset(): void
    {
        $this->client->request(Request::METHOD_GET, self::PASSWORD_RESET_URI.'?bundle=%27-alert("XSS%20TEST%20Mautic")-%27');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $responseData = $clientResponse->getContent();
        // Tests that actual string is not present.
        $this->assertStringNotContainsString('-alert("xss test mautic")-', $responseData, 'XSS injection attempt is filtered.');
        // Tests that sanitized string is passed.
        $this->assertStringContainsString('alertxsstestmautic', $responseData, 'XSS sanitized string is present.');
    }

    public function testPasswordResetPage(): void
    {
        $this->client->request(Request::METHOD_GET, self::PASSWORD_RESET_URI);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $responseData = $clientResponse->getContent();
        $this->assertStringContainsString('Enter either your username or email to reset your password. Instructions to reset your password will be sent to the email in your profile.', $responseData);
    }

    public function testPasswordResetAction(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, self::PASSWORD_RESET_URI);
        $saveButton = $crawler->selectButton('Reset password');
        $form       = $saveButton->form();
        $form['passwordreset[identifier]']->setValue('test@example.com');

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        $responseData = $clientResponse->getContent();
        $this->assertStringContainsString('A new password has been generated and will be emailed to you, if this user exists. If you do not receive it within a few minutes, check your spam box and/or contact the system administrator.', $responseData);
    }

    public function testPasswordResetActionWithoutUserWithSaml(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, self::PASSWORD_RESET_URI);

        // Get the form
        $form = $crawler->filter('form')->form();

        $form->setValues([
            'passwordreset[identifier]' => 'test2@example.com',
        ]);
        $this->client->submit($form);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());
        $this->assertStringContainsString('A new password has been generated and will be emailed to you, if this user exists. If you do not receive it within a few minutes, check your spam box and/or contact the system administrator.', $clientResponse->getContent());
    }
}
