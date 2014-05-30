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
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldValue;
use Mautic\LeadBundle\Entity\LeadIpAddress;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class FieldControllerTest
 *
 * @package Mautic\LeadBundle\Tests\Controller
 */

class FieldControllerTest extends MauticWebTestCase
{

    private function createField()
    {
        $admin = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

        //Create security token in order to be able to create client
        $token = new UsernamePasswordToken($admin, null, 'main', array('ROLE_ADMIN'));
        $this->container->get('security.context')->setToken($token);
        $field = new LeadField();
        $field->setLabel('Lead Field Test');
        $field->setAlias('leadlisttest');
        $field->setType('text');
        $this->em->persist($field);
        $this->em->flush();
        return $field;
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/leads/fields');

        $this->assertNoError($this->client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least the lead-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.leadfield-list')->count()
        );
    }

    public function testNew()
    {
        $crawler = $this->client->request('GET', '/leads/fields/new');
        $this->assertNoError($this->client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#leadfield_label')->count()
        );

        //let's try creating a field
        $form = $crawler->selectButton('leadfield[save]')->form();

        // set some values
        $form['leadfield[label]']      = 'New Field';
        $form['leadfield[type]']       = 'text';

        // submit the form
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.field.notice.created/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testEdit()
    {
        $field = $this->createField();

        $crawler = $this->client->request('GET', '/leads/fields/edit/' . $field->getId());

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#leadfield_label')->count()
        );

        //let's try editing a field
        $form = $crawler->selectButton('leadfield[save]')->form();

        // set some values
        $form['leadfield[label]'] = 'Edit Name';

        // submit the form
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.field.notice.updated/',
            $this->client->getResponse()->getContent()
        );

        //make sure ACL is working
        $field = $this->createField();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/fields/edit/' . $field->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $field = $this->createField();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $this->client->request('GET', '/leads/fields/delete/'.$field->getId());

        $this->assertGreaterThan(
            0,
            $crawler->filter('table.leadfield-list')->count()
        );

        //post to delete
        $crawler = $this->client->request('POST', '/leads/fields/delete/'.$field->getId());

        $this->assertRegExp(
            '/mautic.lead.field.notice.deleted/',
            $this->client->getResponse()->getContent()
        );

        //make sure ACL is working
        $field = $this->createField();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/fields/delete/' . $field->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
