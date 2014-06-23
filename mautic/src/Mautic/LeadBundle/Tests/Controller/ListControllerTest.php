<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class ListControllerTest
 *
 * @package Mautic\LeadBundle\Tests\Controller
 */

class ListControllerTest extends MauticWebTestCase
{

    private function createList()
    {
        $admin = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        //Create security token in order to be able to create client
        $token = new UsernamePasswordToken($admin, null, 'main', array('ROLE_ADMIN'));
        $this->container->get('security.context')->setToken($token);
        $list = new LeadList();
        $list->setName('Lead List Test');
        $list->setAlias('leadlisttest');
        $list->setCreatedBy($admin);
        $list->setIsGlobal(false);
        $list->setFilters(array(
            array(
                'glue'     => 'and',
                'field'    => 'owner',
                'operator' => '=',
                'filter'   => $admin->getId(),
                'display'  => $admin->getName()
            )
        ));
        $this->em->persist($list);
        $this->em->flush();
        return $list;
    }

    public function testIndex()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/leads/lists');

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least the lead-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('div.leadlist-list')->count()
        );
    }

    public function testNew()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/leads/lists/new');

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#leadlist_name')->count()
        );

        /**
        //doesn't work because of dynamically generated fields

        //let's try creating a lead
        $form = $crawler->selectButton('leadlist[save]')->form();

        // set some values
        $form['leadlist[name]']                 = 'My List';
        $form['leadlist[alias]']                = 'mylist';
        $form['leadlist[isGlobal]']             = 0;
        $form['leadlist[isActive]']             = 1;

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.list.notice.created/',
            $client->getResponse()->getContent(),
            'mautic.lead.list.notice.created not found'
        );
        */
    }

    public function testEdit()
    {
        $client = $this->getClient();
        $list = $this->createList();

        $crawler = $client->request('GET', '/leads/lists/edit/' . $list->getId());

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#leadlist_name')->count()
        );

        //let's try creating a lead
        $form = $crawler->selectButton('leadlist[save]')->form();

        // set some values
        $form['leadlist[name]'] = 'Edit Name';

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.list.notice.updated/',
            $client->getResponse()->getContent(),
            'mautic.lead.list.notice.updated not found'
        );

        //make sure ACL is working
        $list = $this->createList();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/lists/edit/' . $list->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $client = $this->getClient();
        $list = $this->createList();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $client->request('GET', '/leads/lists/delete/'.$list->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.leadlist-list')->count()
        );

        //post to delete
        $crawler = $client->request('POST', '/leads/lists/delete/'.$list->getId());

        $this->assertRegExp(
            '/mautic.lead.list.notice.deleted/',
            $client->getResponse()->getContent(),
            'mautic.lead.list.notice.deleted not found'
        );

        //make sure ACL is working
        $list = $this->createList();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/lists/delete/' . $list->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
