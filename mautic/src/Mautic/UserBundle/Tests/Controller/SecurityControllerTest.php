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
 * Class SecurityControllerTest
 *
 * @package Mautic\UserBundle\Tests\Controller
 */

class SecurityControllerTest extends MauticWebTestCase
{

    public function testLogin()
    {
        $this->client->followRedirects(false);
        $crawler = $this->client->request('GET', '/login');

        //should be a 200 code
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('mautic.user.auth.form.loginbtn')->form();

        // submit the form
        $crawler = $this->client->submit($form,
            array(
                '_username' => 'admin',
                '_password' => 'mautic'
            )
        );

        $this->assertNoError($this->client->getResponse(), $crawler);
        $crawler = $this->client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.main-panel-wrapper')->count()
        );
    }
}