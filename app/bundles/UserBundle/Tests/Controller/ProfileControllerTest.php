<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Tests\Traits\CreateEntityTrait;
use Symfony\Component\HttpFoundation\Request;

final class ProfileControllerTest extends MauticMysqlTestCase
{
    use CreateEntityTrait;
    use LoginUserWithSamlTrait;

    protected function setUp(): void
    {
        if (strpos($this->name(), 'WithSaml') > 0) {
            $this->configParams['saml_idp_metadata'] = 'any_string';
        }
        parent::setUp();
    }

    public function testPasswordNotOnAccountPageWithSaml(): void
    {
        $user = $this->createUser($this->createRole(), 'test@example.com');
        $this->em->flush();
        $this->em->clear();

        $this->loginUserWithSaml($user);

        $this->client->request(Request::METHOD_GET, 's/account');

        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('user[plainPassword][password]', (string) $clientResponse->getContent());
        $this->assertStringNotContainsString('user[plainPassword][confirm]', (string) $clientResponse->getContent());
    }

    public function testPasswordOnAccountPageWithoutSaml(): void
    {
        $user = $this->createUser($this->createRole(), 'test@example.com');
        $this->em->flush();
        $this->em->clear();
        $this->loginUser($user);

        $this->client->request(Request::METHOD_GET, 's/account');

        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('user[plainPassword][password]', (string) $clientResponse->getContent());
        $this->assertStringContainsString('user[plainPassword][confirm]', (string) $clientResponse->getContent());
    }

    public function testPasswordFieldsAreNeverPrefilledAndAutofillIsPrevented(): void
    {
        $user = $this->createUser($this->createRole(), 'test@example.com');
        $this->em->flush();
        $this->em->clear();
        $this->loginUser($user);

        $this->client->request(Request::METHOD_GET, 's/account');

        $clientResponse = $this->client->getResponse();
        $content         = (string) $clientResponse->getContent();
        $this->assertResponseIsSuccessful();

        preg_match('/<input[^>]*id="user_plainPassword_password"[^>]*>/', $content, $passwordInput);
        preg_match('/<input[^>]*id="user_plainPassword_confirm"[^>]*>/', $content, $confirmInput);
        $this->assertNotEmpty($passwordInput, 'Password input was not found in the rendered account page.');
        $this->assertNotEmpty($confirmInput, 'Confirm password input was not found in the rendered account page.');

        // The inputs must never carry a pre-filled value attribute.
        $this->assertStringNotContainsString('value=', $passwordInput[0]);
        $this->assertStringNotContainsString('value=', $confirmInput[0]);

        // The stored password hash must never leak into the rendered markup.
        $this->assertStringNotContainsString($user->getPassword(), $content);

        // Browsers and third-party password managers must be told not to offer autofill for these fields.
        foreach ([$passwordInput[0], $confirmInput[0]] as $inputHtml) {
            $this->assertStringContainsString('autocomplete="new-password"', $inputHtml);
            $this->assertStringContainsString('data-lpignore="true"', $inputHtml);
            $this->assertStringContainsString('data-1p-ignore="true"', $inputHtml);
            $this->assertStringContainsString('data-bwignore="true"', $inputHtml);
            $this->assertStringContainsString('data-form-type="other"', $inputHtml);

            // Regression guard: the shared "input-group" form theme used to hardcode
            // a duplicate autocomplete="false" ahead of the field's own attr, which
            // browsers silently prefer over our override since it comes first in the
            // markup. Make sure the attribute is only ever emitted once.
            $this->assertSame(1, substr_count($inputHtml, 'autocomplete='));
        }

        // The fields must be presented through the change-password modal, not inline on the page.
        $this->assertStringContainsString('id="changePasswordModal"', $content);
        $this->assertStringContainsString('data-target="#changePasswordModal"', $content);
    }

    public function testPasswordChangeFlashTranslationsAreExposedToJavascript(): void
    {
        $user = $this->createUser($this->createRole(), 'test@example.com');
        $this->em->flush();
        $this->em->clear();
        $this->loginUser($user);

        $this->client->request(Request::METHOD_GET, 's/account');

        $clientResponse = $this->client->getResponse();
        $content         = (string) $clientResponse->getContent();
        $this->assertResponseIsSuccessful();

        // Mautic.translate() reads from the "javascript" translation domain, which
        // is rendered into the page as the mauticLang JS object. The user.js code
        // that shows a flash when the change-password modal closes relies on these
        // two keys resolving to real strings rather than falling back to the raw id.
        $this->assertStringContainsString('mautic.user.config.account.password.change.pending', $content);
        $this->assertStringContainsString('mautic.user.config.account.password.change.unchanged', $content);
    }
}
