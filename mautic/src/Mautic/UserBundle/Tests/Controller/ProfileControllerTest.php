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
 * Class ProfileControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class ProfileControllerTest extends MauticWebTestCase
{

    protected $dataFixturesPaths = array(
        "UserBundle/DataFixtures/ORM"
    );

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/account');

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        //test to see if what is expected shows up
        $this->assertGreaterThan(
            0,
            $crawler->filter('div.account-wrapper')->count()
        );
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
        $crawler = $this->client->submit($form);

        //form should have failed due to lack of current password
        $this->assertRegExp(
            '/mautic.user.account.password.userpassword/',
            $this->client->getResponse()->getContent()
        );

        $form['user[currentPassword]']  = 'mautic';

        // resubmit the form
        $crawler = $this->client->submit($form);

        $this->assertRegExp(
            '/mautic.user.account.notice.updated/',
            $this->client->getResponse()->getContent()
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
}
