<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class PublicControllerTest extends MauticMysqlTestCase
{
    /**
     * Tests to ensure that xss is prevented on password reset page.
     */
    public function testXssFilterOnPasswordReset(): void
    {
        $this->client->request('GET', '/passwordreset?bundle=%27-alert("XSS%20TEST%20Mautic")-%27');
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
        $this->client->request('GET', '/passwordreset');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $responseData = $clientResponse->getContent();
        $this->assertStringContainsString('Enter either your username or email to reset your password. Instructions to reset your password will be sent to the email in your profile.', $responseData);
    }

    public function testPasswordResetAction(): void
    {
        $crawler    = $this->client->request('GET', '/passwordreset');
        $saveButton = $crawler->selectButton('Reset password');
        $form       = $saveButton->form();
        $form['passwordreset[identifier]']->setValue('test@example.com');

        $crawler        = $this->client->submit($form);
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        $responseData = $clientResponse->getContent();
        $this->assertStringContainsString('A new password has been generated and will be emailed to you, if this user exists. If you do not receive it within a few minutes, check your spam box and/or contact the system administrator.', $responseData);
    }
}
