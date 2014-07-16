<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class UserApiControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller\Api
 */

class UserApiControllerTest extends MauticWebTestCase
{

    private function createEntity()
    {
        $unique = uniqid();

        $role = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneByName('mautic.user.role.admin.name');

        $admin = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        //Create security token in order to be able to create client
        $token = new UsernamePasswordToken($admin, null, 'main', array('ROLE_ADMIN'));
        $this->container->get('security.context')->setToken($token);

        //Create a client
        $entity = new User();
        $entity->setUsername($unique);
        $entity->setEmail($unique . '@mautic.com');
        $entity->setFirstName('API');
        $entity->setLastName('Test');
        $entity->setPosition(('API Tester'));
        $entity->setRole($role);
        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($entity);
        $entity->setPassword($encoder->encodePassword('mautic', $entity->getSalt()));

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->detach($entity);
        return $entity;
    }

    public function testGetEntities()
    {
        $client = $this->getClient();
        $token = $this->getOAuthAccessToken();

        $crawler  = $client->request('GET', '/api/users.json?access_token='.$token);
        $response = $client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);

        $this->assertTrue(isset($decoded['users']) && count($decoded['users'] > 0), 'No users found.');
    }

    public function testNewEntity()
    {
        $client = $this->getClient();
        $token  = $this->getOAuthAccessToken();
        $unique = uniqid();
        $role = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneByName('mautic.user.role.admin.name');

        //purposely leaving out email to test error message
        $data =  array(
            'username'      => $unique,
            'firstName'     => 'API',
            'lastName'      => 'Test',
            'position'      => 'API Tester',
            'role'          => $role->getId(),
            'plainPassword' => array(
                'password' => 'mautic',
                'confirm'  => 'mautic'
            ),
            'isPublished'      => true
        );

        $crawler  = $client->request('POST', '/api/users/new.json?access_token='.$token, $data);
        $response = $client->getResponse();

        //expecting a 400 so figure out what the problem is if we didn't get it
        if ($response->getStatusCode() != 400) {
            $this->assertNoError($response, $crawler);
        }

        $content = $response->getContent();
        $decoded = json_decode($content, true);

        $this->assertTrue((isset($decoded['message']) && $decoded['message'] == 'Validation Failed'),
            'New entity should fail due to missing required fields.'
        );

        $data['email'] = "$unique@mautic.com";

        $crawler  = $client->request('POST', '/api/users/new.json?access_token='.$token, $data);
        $response = $client->getResponse();

        $this->assertNoError($response, $crawler);

        $this->assertEquals(201, $response->getStatusCode(), 'New entity should return with a 201 status code.');

        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername($unique);
        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $this->assertTrue($encoder->isPasswordValid(
                $user->getPassword(), 'mautic', $user->getSalt()
            ), 'The password did not save correctly!'
        );
    }

    public function testGetEntity()
    {
        $client = $this->getClient();
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();

        $crawler  = $client->request('GET', '/api/users/' . $entity->getId() . '.json?access_token='.$token);
        $response = $client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);

        $this->assertTrue(isset($decoded['user']['id']), 'ID not found');
    }

    public function testPatchEditEntity()
    {
        $client = $this->getClient();
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();

        $unique = uniqid();
        $data = array();
        $data['email']     = $unique . "@mautic.com";
        $data['firstName'] = $unique;

        //testing to make sure the password/username does not update which is not allowed via the API
        $data['plainPassword']['password'] = $unique;
        $data['plainPassword']['confirm']  = $unique;
        $data['username']                  = $unique;

        $crawler  = $client->request('PATCH',
            '/api/users/' . $entity->getId() . '/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $client->getResponse();
        $this->assertNoError($response, $crawler);

        //should be JSON content
        $this->assertContentType($response);

        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Edited entity should return with a 200 status code.');

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        $this->assertEquals(
            $decoded['user']['id'],
            $entity->getId()
        );

        //clear the attachments to get new data
        //$this->em->clear();

        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneById($entity->getId());
        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');

        //Test to ensure the patch was applied correctly
        $this->assertTrue($encoder->isPasswordValid(
                $user->getPassword(), 'mautic', $user->getSalt()
            ), 'The password should not have changed!'
        );

        $this->assertEquals(
            $entity->getUsername(), $user->getUsername(),
            'Username was incorrectly changed'
        );
        $this->assertNotEquals(
            $entity->getEmail(), $user->getEmail(),
            'Email was not updated'
        );
        $this->assertEquals(
            $entity->getPosition(), $user->getPosition(),
            'Position was incorrectly changed'
        );
        $this->assertNotEquals(
            $entity->getFirstName(), $user->getFirstName(),
            'First name was not changed'
        );
        $this->assertEquals(
            $entity->getLastName(), $user->getLastName(),
            'First name was incorrectly changed'
        );
    }

    public function testPutEditEntity()
    {
        $client = $this->getClient();
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();

        $unique = uniqid();
        $data = array();
        $data['email']      = $unique . "@mautic.com";
        $data['firstName'] = $unique;

        //testing to make sure the username and password does not update which is not allowed via the API
        $data['plainPassword']['password'] = $unique;
        $data['plainPassword']['confirm']  = $unique;
        $data['username']                  = $unique;

        $crawler  = $client->request('PUT',
            '/api/users/' . $entity->getId() . '/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode(), 'Put should return 400 due to missing fields.');

        $data = array(
            'id'            => $entity->getId(),
            'username'      => $entity->getUsername(),
            'firstName'     => $unique, //updated first name
            'lastName'      => $entity->getLastName(),
            'plainPassword' => array(
                'password' => $unique,
                'confirm'  => $unique,
            ),
            'role'          => $entity->getRole()->getId(),
            'email'         => $unique . "@mautic.com", //updated email
            'position'      => $entity->getPosition(),
            'isPublished'      => true
        );

        //reset the client
        $client->restart();

        //now try with all of entity
        $crawler  = $client->request('PUT',
            '/api/users/' . $entity->getId() . '/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $client->getResponse();
        $this->assertNoError($response, $crawler);

        //should be JSON content
        $this->assertContentType($response);

        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Edited entity should return with a 200 status code.');

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals(
            $decoded['user']['id'],
            $entity->getId()
        );

        //clear attachments in order to retrieve updated data
        //$this->em->clear();

        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername($entity->getUsername());
        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');

        //Test to ensure the put was applied correctly
        $this->assertTrue($encoder->isPasswordValid(
                $user->getPassword(), 'mautic', $user->getSalt()
            ), 'The password should not have changed!'
        );
        /*
        $this->assertEquals(
            $entity->getUsername(), $user->getUsername(),
            'Username was incorrectly changed'
        );
        */
        $this->assertNotEquals(
            $entity->getEmail(), $user->getEmail(),
            'Email was not updated'
        );
        $this->assertEquals(
            $entity->getPosition(), $user->getPosition(),
            'Position was incorrectly changed'
        );
        $this->assertNotEquals(
            $entity->getFirstName(), $user->getFirstName(),
            'First name was not changed'
        );
        $this->assertEquals(
            $entity->getLastName(), $user->getLastName(),
            'First name was incorrectly changed'
        );

        //let's try to put with a new entity to see if it gets created
        $unique = uniqid();
        $data = array(
            'id'            => 1000,
            'username'      => $unique,
            'firstName'     => $unique,
            'lastName'      => $unique,
            'plainPassword' => array(
                'password' => $unique,
                'confirm'  => $unique,
            ),
            'role'          => $entity->getRole()->getId(),
            'email'         => $unique . "@mautic.com", //updated email
            'position'      => $unique,
            'isPublished'      => true
        );

        //reset the client
        $client->restart();

        //now try with all of entity
        $crawler  = $client->request('PUT',
            '/api/users/1000/edit.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($data)
        );
        $response = $client->getResponse();
        $this->assertNoError($response, $crawler);

        //should be JSON content
        $this->assertContentType($response);

        $this->assertEquals(Codes::HTTP_CREATED, $response->getStatusCode(), 'Edited entity should return with a 201 status code.');

        //make sure a user was returned
        $decoded = json_decode($response->getContent(), true);
        $this->assertTrue(
            !empty($decoded['user']['id'])
        );
    }

    public function testDeleteEntity()
    {
        $client = $this->getClient();
        $token  = $this->getOAuthAccessToken();
        $entity = $this->createEntity();
        $crawler  = $client->request('DELETE',
            '/api/users/' . $entity->getId() . '/delete.json?access_token='.$token
        );
        $response = $client->getResponse();
        //should be JSON content
        $this->assertContentType($response);

        $this->assertNoError($response, $crawler);

        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Deleted entity should return with a 200 status code.');

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        /*
        $this->assertEquals(
            $decoded['user']['username'],
            $entity->getUsername()
        );
        */

        //clear attachments in order to retrieve updated data
        // $this->em->clear();

        //make sure the user doesn't exist
        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername($entity->getUsername());

        $this->assertTrue(!$user);
    }

    public function testRoleList()
    {
        $client = $this->getClient();
        $token  = $this->getOAuthAccessToken();
        $crawler  = $client->request('GET',
            '/api/users/list/roles.json?access_token='.$token
        );
        $response = $client->getResponse();
        //should be JSON content
        $this->assertContentType($response);

        $this->assertNoError($response, $crawler);

        //assert the item returned is the same as sent
        $decoded = json_decode($response->getContent(), true);

        $this->assertTrue(
            (is_array($decoded) && count($decoded) > 0),
            'Seems that something is corrupt with the role list'
        );
    }
}