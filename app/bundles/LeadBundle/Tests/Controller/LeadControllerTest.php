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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Entity\IpAddress;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class LeadControllerTest
 *
 * @package Mautic\LeadBundle\Tests\Controller
 */

class LeadControllerTest extends MauticWebTestCase
{

    private function createLead()
    {
        $admin = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        //Create security token in order to be able to create client
        $token = new UsernamePasswordToken($admin, null, 'main', array('ROLE_ADMIN'));
        $this->container->get('security.context')->setToken($token);
        $lead = new Lead();
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress("208.110.200.3", $this->container->get('mautic.factory')->getSystemParameters());
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

    public function testIndex()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/leads');

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least the lead-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('div.leads')->count()
        );

        //make sure ACL is working
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('GET', '/leads');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testNew()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/leads/new');

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#lead_owner_lookup')->count()
        );

        //let's try creating a lead
        $form = $crawler->selectButton('lead[save]')->form();

        // set some values
        $form['lead[field_mobile]']   = '123-123-1234';

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.core.notice.created/',
            $client->getResponse()->getContent(),
            'mautic.core.notice.created not found'
        );

        //make sure ACL is working
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('GET', '/leads/new');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }

    public function testEdit()
    {
        $client = $this->getClient();
        $lead = $this->createLead();

        $crawler = $client->request('GET', '/leads/edit/' . $lead->getId());

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#lead_owner_lookup')->count()
        );

        //let's try creating a lead
        $form = $crawler->selectButton('lead[save]')->form();

        // set some values
        $form['lead[field_firstname]']  = 'Edit Lead';
        $form['lead[field_lastname]']   = 'Test';

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.core.notice.updated/',
            $client->getResponse()->getContent(),
            'mautic.core.notice.updated not found'
        );

        //make sure ACL is working
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('GET', '/leads/edit/' . $lead->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $client = $this->getClient();
        $lead = $this->createLead();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $client->request('GET', '/leads/delete/'.$lead->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.leads')->count()
        );

        //post to delete
        $crawler = $client->request('POST', '/leads/delete/'.$lead->getId());

        $this->assertRegExp(
            '/mautic.core.notice.deleted/',
            $client->getResponse()->getContent(),
            'mautic.core.notice.deleted not found'
        );

        //make sure ACL is working
        $lead = $this->createLead();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/delete/' . $lead->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
