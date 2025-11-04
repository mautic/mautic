<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Tests\Traits\CreateEntityTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class UserLoginTest extends MauticMysqlTestCase
{
    use CreateEntityTrait;

    protected function setUp(): void
    {
        if (strpos($this->name(), 'WithSaml') > 0) {
            $this->configParams['saml_idp_metadata'] = 'any_string';
        }
        parent::setUp();
    }

    /**
     * User can login with acquia email.
     */
    public function testSuccessfulLoginWithAcquiaUserWithSaml(): void
    {
        $this->logoutUser();
        $password = Uuid::uuid4()->toString();
        $this->createUser($this->createRole(), 'test@acquia.com', $password);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/login');

        // Get the form
        $form = $crawler->filter('form')->form();
        $form->setValues([
            '_username' => 'test@acquia.com',
            '_password' => $password,
        ]);
        $crawler = $this->client->submit($form);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());

        // user has logged in
        $title = $crawler->filterXPath('//head/title')->text();
        $this->assertStringContainsString('Dashboard |', $title);
    }
}
