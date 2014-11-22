<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        $client = $this->getClient();
        $crawler = $client->request('GET', '/account');

        //should be a 200 code
        $this->assertNoError($client->getResponse(), $crawler);

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
        $form['user[email]']                    = "{$unique}@mautic.org";
        $form['user[plainPassword][password]']  = 'mautic';
        $form['user[plainPassword][confirm]']   = 'mautic';

        // submit the form
        $crawler = $client->submit($form);

        //form should have failed due to lack of current password
        $this->assertRegExp(
            '/mautic.user.account.password.userpassword/',
            $client->getResponse()->getContent(),
            'mautic.user.account.password.userpassword not found'
        );

        $form['user[currentPassword]']  = 'mautic';

        // resubmit the form
        $crawler = $client->submit($form);

        $this->assertRegExp(
            '/mautic.user.account.notice.updated/',
            $client->getResponse()->getContent(),
            'mautic.user.account.notice.updated not found'
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
