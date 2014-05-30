<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadFieldValue;
use Mautic\LeadBundle\Entity\LeadIpAddress;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class LeadApiControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller\Api
 */

class LeadApiControllerTest extends MauticWebTestCase
{

    private function createEntity()
    {
        $admin = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        //Create security token in order to be able to create client
        $token = new UsernamePasswordToken($admin, null, 'main', array('ROLE_ADMIN'));
        $this->container->get('security.context')->setToken($token);
        $lead = new Lead();
        $ipAddress = new LeadIpAddress();
        $ipAddress->setIpAddress("208.110.200.3");
        $lead->addIpAddress($ipAddress);

        $fieldValue = new LeadFieldValue();

        $field = $this->em
            ->getRepository('MauticLeadBundle:LeadField')
            ->findOneByAlias('mobile');

        $fieldValue->setField($field);
        $fieldValue->setValue('222-222-2222');
        $fieldValue->setLead($lead);
        $lead->addField($fieldValue);

        $this->em->persist($lead);
        $this->em->flush();
        return $lead;
    }

    public function testGetEntities()
    {
        $token = $this->getOAuthAccessToken();

        $crawler  = $this->client->request('GET', '/api/leads.json?access_token='.$token);
        $response = $this->client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(isset($decoded['leads']) && count($decoded['leads'] > 0), 'No leads found.');
    }

    public function testNewEntity()
    {
        $token  = $this->getOAuthAccessToken();

        $admin = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        $data =  array(
            'customFields'  => array(
                'mobile' => '333-333-3333'
            ),
            'owner'         => $admin->getId()
        );

        $crawler  = $this->client->request('POST', '/api/leads/new.json?access_token='.$token, $data);
        $response = $this->client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertEquals(201, $response->getStatusCode(), 'New entity should return with a 201 status code.');
    }

    public function testGetEntity()
    {
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();

        $crawler  = $this->client->request('GET', '/api/leads/' . $entity->getId() . '.json?access_token='.$token);
        $response = $this->client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(isset($decoded['lead']['id']), 'ID not found');
    }

    public function testPatchEditEntity()
    {
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();

        $data =  array(
            'field_firstname'  => 'API',
            'field_lastname'   => 'Test Update'
        );

        $crawler  = $this->client->request('PATCH',
            '/api/leads/' . $entity->getId() . '/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $this->client->getResponse();
        $this->assertNoError($response, $crawler);

        //should be JSON content
        $this->assertContentType($response);

        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Edited entity should return with a 200 status code.');

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals(
            $decoded['lead']['id'],
            $entity->getId()
        );
    }

    public function testPutEditEntity()
    {
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();

        //purposely leaving out email to test error message
        $data =  array(
            'field_firstname'  => 'API',
            'field_lastname'   => 'Test Update'
        );

        $crawler  = $this->client->request('PUT',
            '/api/leads/' . $entity->getId() . '/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $this->client->getResponse();

        //should be JSON content
        $this->assertContentType($response);

        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Edited entity should return with a 200 status code.');

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals(
            $decoded['lead']['id'],
            $entity->getId()
        );

        //let's try to put with a new entity to see if it gets created
        $data =  array(
            'field_firstname'  => 'API',
            'field_lastname'   => 'Test Update'
        );

        //reset the client
        $this->client->restart();

        //now try with all of entity
        $crawler  = $this->client->request('PUT',
            '/api/leads/1000/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $this->client->getResponse();
        $this->assertNoError($response, $crawler);

        //should be JSON content
        $this->assertContentType($response);

        $this->assertEquals(Codes::HTTP_CREATED, $response->getStatusCode(), 'Edited entity should return with a 201 status code.');

        //make sure a user was returned
        $decoded = json_decode($response->getContent(), true);
        $this->assertTrue(
            !empty($decoded['lead']['id'])
        );
    }

    public function testDeleteEntity()
    {
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();
        $id     = $entity->getId();
        $crawler  = $this->client->request('DELETE',
            '/api/leads/' . $entity->getId() . '/delete.json?access_token='.$token
        );
        $response = $this->client->getResponse();
        //should be JSON content
        $this->assertContentType($response);

        $this->assertNoError($response, $crawler);

        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Deleted entity should return with a 200 status code.');

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        $this->assertTrue(
            !empty($decoded['lead'])
        );

        //clear attachments in order to retrieve updated data
        $this->em->clear();

        //make sure the lead doesn't exist
        $lead = $this->em
            ->getRepository('MauticLeadBundle:Lead')
            ->findOneById($id);

        $this->assertTrue(!$lead);
    }

    public function testOwnerList()
    {
        $token  = $this->getOAuthAccessToken();
        $crawler  = $this->client->request('GET',
            '/api/leads/list/owners.json?access_token='.$token
        );
        $response = $this->client->getResponse();
        //should be JSON content
        $this->assertContentType($response);

        $this->assertNoError($response, $crawler);

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        $this->assertTrue(
            (is_array($decoded) && count($decoded) > 0),
            'Seems that something is corrupt with the owner list'
        );
    }

    public function testListsList()
    {
        $token  = $this->getOAuthAccessToken();
        $crawler  = $this->client->request('GET',
            '/api/leads/list/lists.json?access_token='.$token
        );
        $response = $this->client->getResponse();
        //should be JSON content
        $this->assertContentType($response);

        $this->assertNoError($response, $crawler);

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        $this->assertTrue(
            (is_array($decoded) && count($decoded) > 0),
            'Seems that something is corrupt with the smartlist list'
        );
    }

    public function testFieldsList()
    {
        $token  = $this->getOAuthAccessToken();
        $crawler  = $this->client->request('GET',
            '/api/leads/list/fields.json?access_token='.$token
        );
        $response = $this->client->getResponse();
        //should be JSON content
        $this->assertContentType($response);

        $this->assertNoError($response, $crawler);

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        $this->assertTrue(
            (is_array($decoded) && count($decoded) > 0),
            'Seems that something is corrupt with the fields list'
        );
    }
}