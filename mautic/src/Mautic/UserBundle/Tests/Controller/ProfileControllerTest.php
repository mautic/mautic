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
 * Class ProfileControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class ProfileControllerTest extends WebTestCase
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

        $crawler = $client->request('GET', '/account');

        //should be a 200 code
        $this->assertTrue($client->getResponse()->isSuccessful());

        //test to see if what is expcted shows up
        $this->assertGreaterThan(
            0,
            $crawler->filter('div.account-wrapper')->count()
        );

        $client->followRedirects();

        //let's try creating a user
        $form = $crawler->selectButton('btn-save-profile')->form();

        // set some values
        $unique                                 = uniqid();
        $form['user[firstName]']                = 'Test';
        $form['user[lastName]']                 = 'User';
        $form['user[email]']                    = "{$unique}@mautic.com";
        $form['user[plainPassword][password]']  = 'mautic';
        $form['user[plainPassword][confirm]']   = 'mautic';

        // submit the form
        $crawler = $client->submit($form);

        //form should have failed due to lack of current password
        $this->assertRegExp(
            '/Current password is incorrect/',
            $client->getResponse()->getContent()
        );

        $form['user[currentPassword]']  = 'mautic';

        // resubmit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/has been updated/',
            $client->getResponse()->getContent()
        );


        //ensure that the password updated is still correct
        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneByUsername('admin');

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
