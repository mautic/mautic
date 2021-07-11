<?php

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends MauticMysqlTestCase
{
    /**
     * Get user's create page.
     */
    public function testNewActionUser(): void
    {
        $this->client->request('GET', '/s/users/new/');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }
}
