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

/**
 * Class UserControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class UserControllerTest extends MauticWebTestCase
{

    private function createUser()
    {
        $crawler = $this->client->request('GET', '/users/new');

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
        $crawler = $this->client->submit($form);

        //ensure that the password created is correct
        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername($unique);

        return array($user, $crawler);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/users');

        $this->assertNoError($this->client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least the user-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.user-list')->count()
        );
    }

    public function testNew()
    {
        $crawler = $this->client->request('GET', '/users/new');
        $this->assertNoError($this->client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#user_username')->count()
        );

        list($user, $crawler) = $this->createUser();

        $this->assertRegExp(
            '/mautic.user.user.notice.created/',
            $this->client->getResponse()->getContent()
        );

        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $this->assertTrue($encoder->isPasswordValid(
            $user->getPassword(), 'mautic', $user->getSalt()
        ));
    }

    public function testEdit()
    {
        list($user, $crawler) = $this->createUser();

        $crawler = $this->client->request('GET', '/users/edit/' . $user->getId());

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

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
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.user.user.notice.updated/',
            $this->client->getResponse()->getContent()
        );

        //ensure that the password created didn't get overwritten or get blanked out
        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $this->assertTrue($encoder->isPasswordValid(
            $user->getPassword(), 'mautic', $user->getSalt()
        ));
    }

    public function testDelete()
    {
        list($user, $crawler) = $this->createUser();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $this->client->request('GET', '/users/delete/'.$user->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('table.user-list')->count()
        );

        //post to delete
        $crawler = $this->client->request('POST', '/users/delete/'.$user->getId());

        $this->assertRegExp(
            '/mautic.user.user.notice.deleted/',
            $this->client->getResponse()->getContent()
        );
    }
}
