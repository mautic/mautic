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
 * Class UserControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class UserControllerTest extends WebTestCase
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

        $crawler = $client->request('GET', '/users');

        //should be a 200 code
        $this->assertTrue($client->getResponse()->isSuccessful());

        //test to see if at least the user-list table is displayed
        $this->assertGreaterThan(
            0,
            $crawler->filter('table.user-list')->count()
        );
    }

    public function testNew()
    {

        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'mautic',
        ));

        $client->followRedirects();

        $crawler = $client->request('GET', '/users/new');

        //should be a 200 code
        $this->assertTrue($client->getResponse()->isSuccessful());

        //test to see if at least one form element is present
        $this->assertGreaterThan(
            0,
            $crawler->filter('#user_username')->count()
        );

        //let's try creating a user
        $form = $crawler->selectButton('user[save]')->form();

        // set some values
        $unique                                 = uniqid();
        $form['user[username]']                 = $unique;
        $form['user[firstName]']                = 'Test';
        $form['user[lastName]']                 = 'User';
        $form['user[position]']                 = 'Tester';
        $form['user[email]']                    = "{$unique}@mautic.com";
        $form['user[role]']                     = '1';
        $form['user[plainPassword][password]']  = 'mautic';
        $form['user[plainPassword][confirm]']   = 'mautic';

        // submit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/has been created/',
            $client->getResponse()->getContent()
        );

        //ensure that the password created is correct
        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername($unique);

        $encoder = $this->encoder->getEncoder('Mautic\UserBundle\Entity\User');
        $this->assertTrue($encoder->isPasswordValid(
            $user->getPassword(), 'mautic', $user->getSalt()
        ));
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
