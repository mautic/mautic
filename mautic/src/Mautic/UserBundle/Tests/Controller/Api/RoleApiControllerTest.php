<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticWebTestCase;

/**
 * Class RoleApiControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller\Api
 */

class RoleApiControllerTest extends MauticWebTestCase
{

    public function testGetEntities()
    {
        $token    = $this->getOAuthAccessToken();
        $crawler  = $this->client->request('GET', '/api/roles.json?access_token='.$token);
        $response = $this->client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(isset($decoded['roles']) && count($decoded['roles'] > 0), 'No roles found.');
    }

    public function testGetEntity()
    {
        $token = $this->getOAuthAccessToken();
        $role = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneByName('mautic.user.role.admin.name');

        $crawler  = $this->client->request('GET', '/api/roles/' . $role->getId() . '.json?access_token='.$token);
        $response = $this->client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(isset($decoded['role']['id']), 'ID not found');
    }

}