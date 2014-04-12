<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class RoleControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class RoleControllerTest extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $encoder;

    public function testIndex()
    {

        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'mautic',
        ));

        $client->followRedirects();

        $crawler = $client->request('GET', '/roles');

        //should be a 200 code
        $this->assertTrue($client->getResponse()->isSuccessful());

        //test to see if at least the role-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.role-list')->count()
        );
    }

    public function testNew()
    {

        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'mautic',
        ));

        $client->followRedirects();

        $crawler = $client->request('GET', '/roles/new');

        //should be a 200 code
        $this->assertTrue($client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#role_name')->count()
        );

        //let's try creating a user
        $form = $crawler->selectButton('role[save]')->form();

        // set some values
        $unique                                    = uniqid();
        $form['role[name]']                        = $unique;
        $form['role[description]']                 = 'Functional Test';
        $form['role[isAdmin]']                     = 0;
        $form['role[permissions][api:access][1]']->tick();
        $form['role[permissions][user:users][0]']->tick();

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/has been created/',
            $client->getResponse()->getContent()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->encoder = static::$kernel->getContainer()
            ->get('security.encoder_factory');
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}
