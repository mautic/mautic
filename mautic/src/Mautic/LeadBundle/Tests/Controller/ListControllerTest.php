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
        $crawler = $this->client->request('GET', '/leads/lists');

        $this->assertNoError($this->client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least the lead-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('div.leadlist-list')->count()
        );
    }

    public function testNew()
    {
        $crawler = $this->client->request('GET', '/leads/lists/new');
        $this->assertNoError($this->client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

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
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.list.notice.created/',
            $this->client->getResponse()->getContent()
        );
        */
    }

    public function testEdit()
    {
        $list = $this->createList();

        $crawler = $this->client->request('GET', '/leads/lists/edit/' . $list->getId());

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

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
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.list.notice.updated/',
            $this->client->getResponse()->getContent()
        );

        //make sure ACL is working
        $list = $this->createList();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/lists/edit/' . $list->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $list = $this->createList();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $this->client->request('GET', '/leads/lists/delete/'.$list->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.leadlist-list')->count()
        );

        //post to delete
        $crawler = $this->client->request('POST', '/leads/lists/delete/'.$list->getId());

        $this->assertRegExp(
            '/mautic.lead.list.notice.deleted/',
            $this->client->getResponse()->getContent()
        );

        //make sure ACL is working
        $list = $this->createList();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/lists/delete/' . $list->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
