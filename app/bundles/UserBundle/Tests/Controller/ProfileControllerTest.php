<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Tests\Traits\CreateEntityTrait;
use Symfony\Component\HttpFoundation\Request;

class ProfileControllerTest extends MauticMysqlTestCase
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
        $this->assertEquals(200, $clientResponse->getStatusCode());
        $this->assertStringNotContainsString('user[plainPassword][password]', $clientResponse->getContent());
        $this->assertStringNotContainsString('user[plainPassword][confirm]', $clientResponse->getContent());
    }

    public function testPasswordOnAccountPageWithoutSaml(): void
    {
        $user = $this->createUser($this->createRole(), 'test@example.com');
        $this->em->flush();
        $this->em->clear();
        $this->loginUser($user);

        $this->client->request(Request::METHOD_GET, 's/account');

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());
        $this->assertStringContainsString('user[plainPassword][password]', $clientResponse->getContent());
        $this->assertStringContainsString('user[plainPassword][confirm]', $clientResponse->getContent());
    }
}
