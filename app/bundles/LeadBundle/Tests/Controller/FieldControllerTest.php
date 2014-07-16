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
use Mautic\LeadBundle\Entity\LeadField;
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
        $client = $this->getClient();
        $crawler = $client->request('GET', '/leads/fields');

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

        //test to see if at least the lead-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.leadfield-list')->count()
        );
    }

    public function testNew()
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/leads/fields/new');
        $this->assertNoError($client->getResponse(), $crawler);

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

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
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.lead.field.notice.created/',
            $client->getResponse()->getContent(),
            'mautic.lead.field.notice.created not found'
        );
    }

    public function testEdit()
    {
        $client = $this->getClient();
        $field = $this->createField();

        $crawler = $client->request('GET', '/leads/fields/edit/' . $field->getId());

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

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
        $crawler = $client->submit($form);
        $this->assertNoError($client->getResponse(), $crawler);

        $this->assertRegExp(
            '/mautic.lead.field.notice.updated/',
            $client->getResponse()->getContent(),
            'mautic.lead.field.notice.updated not found'
        );

        //make sure ACL is working
        $field = $this->createField();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/fields/edit/' . $field->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $client = $this->getClient();
        $field = $this->createField();

        //ensure we are redirected to list as get should not be allowed
        $crawler = $client->request('GET', '/leads/fields/delete/'.$field->getId());
        $this->assertNoError($client->getResponse(), $crawler);
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.leadfield-list')->count()
        );

        //post to delete
        $crawler = $client->request('POST', '/leads/fields/delete/'.$field->getId());

        $this->assertRegExp(
            '/mautic.lead.field.notice.deleted/',
            $client->getResponse()->getContent(),
            'mautic.lead.field.notice.deleted not found'
        );

        //make sure ACL is working
        $field = $this->createField();
        $client = $this->getNonAdminClient('limitedsales');
        $client->request('POST', '/leads/fields/delete/' . $field->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
