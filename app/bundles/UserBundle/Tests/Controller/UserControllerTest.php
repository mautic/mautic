<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Tests\Traits\CreateEntityTrait;
use Symfony\Component\HttpFoundation\Request;

class UserControllerTest extends MauticMysqlTestCase
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
        $this->assertEquals(200, $clientResponse->getStatusCode());
        $this->assertStringNotContainsString('user[plainPassword][password]', $clientResponse->getContent());
        $this->assertStringNotContainsString('user[plainPassword][confirm]', $clientResponse->getContent());
    }
}
