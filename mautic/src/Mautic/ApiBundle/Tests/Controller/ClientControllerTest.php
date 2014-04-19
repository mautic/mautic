<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests\Controller;

use Mautic\ApiBundle\Entity\Client;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class ClientControllerTest
 *
 * @package Mautic\ApiBundle\Tests\Controller
 */

class ClientControllerTest extends MauticWebTestCase
{
    public $createdClientId = 0;

    private function createApiClient()
    {
        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        //Create security token in order to be able to create client
        $token = new UsernamePasswordToken($user, null, 'main', array('ROLE_ADMIN'));
        $this->container->get('security.context')->setToken($token);

        //Create a client
        $client = new Client();
        $client->setName("Login Test");
        $client->setRedirectUris(array("https://mautic.com"));
        $this->container->get('mautic.model.client')->saveEntity($client);

        return $client;
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/clients');

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least the role-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.client-list')->count()
        );
    }

    public function testNew()
    {
        $crawler = $this->client->request('GET', '/clients/new');

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#client_name')->count()
        );

        //let's try creating a user
        $form = $crawler->selectButton('client[save]')->form();

        // set some values
        $unique                          = uniqid();
        $form['client[name]']            = $unique;
        $form['client[redirectUris]']    = 'http://mautic.com';

        // submit the form
        $crawler = $this->client->submit($form);

        //should fail because of not using a secure redirectUri
        $this->assertRegExp(
            '/mautic.api.client.redirecturl.invalid/',
            $this->client->getResponse()->getContent()
        );

        //try again using secure URIs
        $form['client[redirectUris]'] = 'https://mautic.com';

        // submit the form
        $crawler = $this->client->submit($form);

        //should be successful
        $this->assertRegExp(
            '/mautic.api.client.notice.created/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testEdit()
    {
        $apiClient = $this->createApiClient();

        $crawler = $this->client->request('GET', '/clients/edit/'.$apiClient->getId());

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#client_name')->count()
        );

        //let's try creating a user
        $form = $crawler->selectButton('client[save]')->form();

        // set some values
        $form['client[redirectUris]']    = 'https://mautic-test.com';

        // submit the form
        $crawler = $this->client->submit($form);

        //success?
        $this->assertRegExp(
            '/mautic.api.client.notice.updated/',
            $this->client->getResponse()->getContent()
        );

        //make sure the client id and secret remained the same
        $updatedClient = $this->em
            ->getRepository('MauticApiBundle:Client')
            ->findOneById($apiClient->getId());

        $this->assertEquals($updatedClient->getRandomId(), $apiClient->getRandomId());
        $this->assertEquals($updatedClient->getSecret(), $apiClient->getSecret());
    }

    public function testDelete()
    {
        $apiClient = $this->createApiClient();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $this->client->request('GET', '/clients/delete/'.$apiClient->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('table.client-list')->count()
        );

        //post to delete
        $crawler = $this->client->request('POST', '/clients/delete/'.$apiClient->getId());

        $this->assertRegExp(
            '/mautic.api.client.notice.deleted/',
            $this->client->getResponse()->getContent()
        );
    }
}
