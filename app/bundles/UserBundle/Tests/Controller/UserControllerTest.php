<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Tests\Traits\CreateEntityTrait;
use Symfony\Component\HttpFoundation\Request;

final class UserControllerTest extends MauticMysqlTestCase
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

    public function testPasswordFieldsOnEditUserPageWithSaml(): void
    {
        $user1 = $this->createUser($this->createRole(), 'test2@example.com');
        $user2 = $this->createUser($this->createRole(true), 'test@example.com');
        $this->em->flush();
        $this->em->clear();

        $this->loginUserWithSaml($user2);

        $this->client->request(Request::METHOD_GET, 's/users/edit/'.$user1->getId());

        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('user[plainPassword][password]', (string) $clientResponse->getContent());
        $this->assertStringNotContainsString('user[plainPassword][confirm]', (string) $clientResponse->getContent());
    }

    public function testEditUserPageShowsPasswordInChangePasswordModal(): void
    {
        $admin      = $this->createUser($this->createRole(true), 'admin@example.com');
        $user       = $this->createUser($this->createRole(), 'test2@example.com');
        $this->em->flush();
        $this->em->clear();
        $this->loginUser($admin);

        $this->client->request(Request::METHOD_GET, 's/users/edit/'.$user->getId());

        $content = (string) $this->client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('data-target="#changePasswordModal"', $content);
        $this->assertStringContainsString('data-staged-message="mautic.user.user.password.change.pending"', $content);
        $this->assertStringContainsString('user[plainPassword][password]', $content);
        $this->assertStringContainsString('user[plainPassword][confirm]', $content);

        preg_match('/<input[^>]*id="user_plainPassword_password"[^>]*>/', $content, $passwordInput);
        $this->assertNotEmpty($passwordInput);
        $this->assertStringNotContainsString('value=', $passwordInput[0]);
        $this->assertStringContainsString('autocomplete="new-password"', $passwordInput[0]);
        $this->assertSame(1, substr_count($passwordInput[0], 'autocomplete='));
    }

    public function testNewUserPageKeepsRequiredPasswordFieldsInline(): void
    {
        $admin = $this->createUser($this->createRole(true), 'admin@example.com');
        $this->em->flush();
        $this->em->clear();
        $this->loginUser($admin);

        $this->client->request(Request::METHOD_GET, 's/users/new');

        $content = (string) $this->client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('user[plainPassword][password]', $content);
        $this->assertStringNotContainsString('id="changePasswordModal"', $content);
    }
}
