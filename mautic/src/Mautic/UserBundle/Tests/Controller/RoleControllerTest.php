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
 * Class RoleControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class RoleControllerTest extends MauticWebTestCase
{

    private function createRole()
    {
        $crawler = $this->client->request('GET', '/roles/new');

        //let's try creating a user
        $form = $crawler->selectButton('role[save]')->form();

        // set some values
        $unique                    = uniqid();
        $form['role[name]']        = $unique;
        $form['role[description]'] = 'Functional Test';
        $form['role[isAdmin]']     = 0;
        $form['role[permissions][api:access][1]']->tick();
        $form['role[permissions][user:users][0]']->tick();

        // submit the form
        $crawler = $this->client->submit($form);

        $role = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneByName($unique);
        return array($role, $crawler);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/roles');

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least the role-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.role-list')->count()
        );
    }

    public function testNew()
    {
        $crawler = $this->client->request('GET', '/roles/new');

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#role_name')->count()
        );

        list($role, $crawler) = $this->createRole();

        $this->assertRegExp(
            '/mautic.user.role.notice.created/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testEdit()
    {
        list($role, $crawler) = $this->createRole();

        $crawler = $this->client->request('GET', '/roles/edit/' . $role->getId());

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#role_name')->count()
        );

        //let's try creating a user
        $form = $crawler->selectButton('role[save]')->form();

        // set some values
        $form['role[description]'] = 'Role Edit Test';

        // submit the form
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.user.role.notice.updated/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testDelete()
    {
        list($role, $crawler) = $this->createRole();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $this->client->request('GET', '/roles/delete/' . $role->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('table.role-list')->count()
        );

        //post to delete
        $crawler = $this->client->request('POST', '/roles/delete/' . $role->getId());

        $this->assertRegExp(
            '/mautic.user.role.notice.deleted/',
            $this->client->getResponse()->getContent()
        );
    }
}
