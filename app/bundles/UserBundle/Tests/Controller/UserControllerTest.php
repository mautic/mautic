<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\UserBundle\Entity\User;

/**
 * Class UserControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class UserControllerTest extends MauticWebTestCase
{

    private function createUser()
    {
        $role = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneByName('mautic.user.role.admin.name');

        $unique  = uniqid();
        $entity = new User();
        $entity->setUsername($unique);
        $entity->setFirstName('Test');
        $entity->setLastName('User');
        $entity->setPosition('Tester');
        $entity->setEmail("{$unique}@mautic.com");
        $entity->setRole($role);
        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $entity->setPassword($encoder->encodePassword('mautic', $entity->getSalt()));

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->detach($entity);
        return $entity;
    }

    public function testIndex()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/users');

        $this->assertNoError($client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least the user-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.user-list')->count()
        );

        //make sure ACL is working
        $client = $this->getNonAdminClient();
        $client->request('GET', '/users');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testNew()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/users/new');
        $this->assertNoError($client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#user_username')->count()
        );
        $crawler = $client->request('GET', '/users/new');

        //let's try creating a user
        $form = $crawler->selectButton('user[save]')->form();

        $role = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneByName('mautic.user.role.admin.name');

        // set some values
        $unique                                 = uniqid();
        $form['user[username]']                 = $unique;
        $form['user[firstName]']                = 'Test';
        $form['user[lastName]']                 = 'User';
        $form['user[position]']                 = 'Tester';
        $form['user[email]']                    = "{$unique}@mautic.com";
        $form['user[role]']                     = $role->getId();
        $form['user[plainPassword][password]']  = 'mautic';
        $form['user[plainPassword][confirm]']   = 'mautic';

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.core.notice.created/',
            $client->getResponse()->getContent(),
            'mautic.core.notice.created not found'
        );

        //ensure that the password created is correct
        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername($unique);

        $this->assertNotEquals($user, null);

        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $this->assertTrue($encoder->isPasswordValid(
            $user->getPassword(), 'mautic', $user->getSalt()
        ));

        //make sure ACL is working
        $client = $this->getNonAdminClient();
        $client->request('GET', '/users/new');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }

    public function testEdit()
    {
        $client = $this->getClient();
        $user   = $this->createUser();

        $crawler = $client->request('GET', '/users/edit/' . $user->getId());

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#user_username')->count()
        );

        //let's try creating a user
        $form = $crawler->selectButton('user[save]')->form();

        // set some values
        $form['user[firstName]']                = 'Edit User';
        $form['user[lastName]']                 = 'Test';

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.core.notice.updated/',
            $client->getResponse()->getContent(),
            'mautic.core.notice.updated not found'
        );

        //ensure that the password created didn't get overwritten or get blanked out
        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $this->assertTrue($encoder->isPasswordValid(
            $user->getPassword(), 'mautic', $user->getSalt()
        ));

        //make sure ACL is working
        $client = $this->getNonAdminClient();
        $client->request('GET', '/users/edit/' . $user->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $client = $this->getClient();
        $user = $this->createUser();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $client->request('GET', '/users/delete/'.$user->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('table.user-list')->count()
        );

        //post to delete
        $crawler = $client->request('POST', '/users/delete/'.$user->getId());

        $this->assertRegExp(
            '/mautic.core.notice.deleted/',
            $client->getResponse()->getContent(),
            'mautic.core.notice.deleted not found'
        );

        //make sure ACL is working
        $user = $this->createUser();
        $client = $this->getNonAdminClient();
        $client->request('POST', '/users/delete/' . $user->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
