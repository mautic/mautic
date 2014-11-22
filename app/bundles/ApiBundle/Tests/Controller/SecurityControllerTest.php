<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests\Controller;

use FOS\RestBundle\Util\Codes;
use Mautic\CoreBundle\Test\MauticWebTestCase;

/**
 * Class SecurityControllerTest
 *
 * @package Mautic\ApiBundle\Tests\Controller
 */

class SecurityControllerTest extends MauticWebTestCase
{
    public function testOAuthLogin()
    {
        $access_token = $this->getOAuthAccessToken(true);
        $client       = $this->getAnonClient();

        //test the API
        $crawler  = $client->request('GET', 'api/users?access_token=' . $access_token);
        $response = $client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(isset($decoded['users']) && count($decoded['users'] > 0), 'No users found.');
    }

    public function testApiAccessRestriction()
    {
        $anonClient = $this->getAnonClient();

        $anonClient->followRedirects(false);

        $client = $this->em->getRepository('MauticApiBundle:oAuth2\Client')->findOneByName('Mautic');
        $redirectUris = $client->getRedirectUris();
        $redirectUri  = urlencode($redirectUris[0]);
        $anonClient->request('GET', 'oauth/v2/auth?client_id=' . $client->getPublicId() . '&response_type=code&redirect_uri=' . $redirectUri);
        $crawler = $anonClient->followRedirect();

        $this->assertNoError($anonClient->getResponse(), $crawler);

        $formLogin = $crawler->filter('form.form-login')->count();

        //Should have an OAuth login form
        $this->assertGreaterThan(
            0,
            $formLogin
        );

        //Let's login
        $form = $crawler->selectButton('mautic.user.auth.form.loginbtn')->form();

        // submit the form
        $crawler = $anonClient->submit($form,
            array(
                '_username' => 'sales',
                '_password' => 'mautic'
            )
        );
        $crawler = $anonClient->followRedirect();

        $this->assertEquals(Codes::HTTP_FORBIDDEN, $anonClient->getResponse()->getStatusCode(), 'Unauthorized access to API');
    }

    public function testIsGranted()
    {
        $user   = $this->em->getRepository('MauticUserBundle:User')->findOneByUsername('sales');
        $token  = $this->getOAuthAccessToken(true);
        $client = $this->getAnonClient();

        //test the api
        $permissions = array(
            'permissions' => array(
                "api:access:full",
                "user:users:edit",
                "user:roles:new"
            )
        );

        $crawler  = $client->request('POST', '/api/users/' . $user->getId() . '/permissioncheck.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($permissions)
        );

        $response = $client->getResponse();

        //should get a 404 because user:roles:new is not correct
        $this->assertEquals(Codes::HTTP_NOT_FOUND, $response->getStatusCode(), 'User:roles:new was not correctly detected as invalid');

        $permissions = array(
            'permissions' => array(
                "api:access:full",
                "user:users:edit"
            )
        );

        $crawler  = $client->request('POST', '/api/users/' . $user->getId() . '/permissioncheck.json?access_token='.$token,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($permissions)
        );

        $response = $client->getResponse();

        $this->assertNoError($response, $crawler);
        $this->assertContentType($response);

        $content = $response->getContent();
        $decoded = json_decode($content, true);

        $this->assertTrue(
            (isset($decoded['api:access:full']) && isset($decoded['user:users:edit'])),
            'Permissions object was not returned in the correct format'
        );
    }
}