<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures as Fixtures;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MauticWebTestCase extends WebTestCase
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var
     */
    protected $encoder;

    /**
     * @var
     */
    protected $container;

    protected function getClient(array $options = array(), array $server = array())
    {
        if (empty($server['PHP_AUTH_USER'])) {
            $server['PHP_AUTH_USER'] = 'admin';
            $server['PHP_AUTH_PW']   = 'mautic';
        }
        $client = static::createClient($options, $server);

        $client->followRedirects(true);
        return $client;
    }

    protected function getAnonClient(array $options = array(), array $server = array())
    {
        $client = static::createClient($options, $server);

        $client->followRedirects(true);
        return $client;
    }

    protected function getOAuthAccessToken($fullTest = false)
    {
        $anonClient = $this->getAnonClient();

        $anonClient->followRedirects(false);

        $client = $this->em->getRepository('MauticApiBundle:Client')->findOneByName('Mautic');
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
                '_username' => 'admin',
                '_password' => 'mautic'
            )
        );
        $this->assertNoError($anonClient->getResponse(), $crawler);

        $crawler = $anonClient->followRedirect();
        $this->assertNoError($anonClient->getResponse(), $crawler);

        $authorize = $crawler->filter('input.btn-accept')->count();

        if ($authorize || $fullTest) {
            //Should now have the oauth accept/deny form
            $form = $crawler->selectButton('mautic.api.oauth.accept')->form();

            //Let's authorize
            $crawler = $anonClient->submit($form);
        } elseif ($fullTest) {
            $this->assertTrue($authorize, "Could not find the authorization form.");
        }

        //should get a redirect header
        $location = $anonClient->getResponse()->headers->get('location');
        $this->assertTrue(!empty($location));

        //get the code
        $code = str_replace($redirectUris[0]."?code=", "", $location);
        $this->assertTrue(!empty($code));

        //reset the client
        $anonClient = $this->getAnonClient();

        //submit for a token
        $anonClient->request('GET', 'oauth/v2/token?client_id=' . $client->getPublicId() . '&client_secret=' .
            $client->getSecret() . '&grant_type=authorization_code&redirect_uri=' . $redirectUri .
            '&code=' . $code);

        $this->assertNoError($anonClient->getResponse(), $crawler);

        //get the access token
        $response = $anonClient->getResponse();
        $this->assertNoError($response, $crawler);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(!empty($decoded["access_token"]));

        return $decoded["access_token"];

    }

    protected function assertContentType($response, $type = "application/json")
    {
        $this->assertTrue(
            $response->headers->contains('Content-Type', $type), 'Unrecognized content type. Expecting ' . $type .
            '; received ' . $response->headers->get('Content-Type')
        );
    }

    protected function assertNoError($response, $crawler)
    {
        $noException = true;
        $msg         = "Status code " . $response->getStatusCode();
        if ($response->getStatusCode() >= 400) {
            //symfony inserts the exception into the title so extract it from that
            if (count($crawler)) {
                $msg .= ": " . trim($crawler->filter('title')->text());
            } elseif ($response->getContent()) {
                if ($response->headers->get('Content-Type') == 'application/json') {
                    $msg .= ": " . print_r(json_decode($response->getContent()), true);
                } else {
                    $msg .= ": " . $response->getContent();
                }
            }
            $noException = false;
        }
        $this->assertTrue($noException, $msg);
    }


    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();

        //setup the request stack
        $request = Request::createFromGlobals();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $this->container->set('request_stack', $requestStack);

        //setup the entity manager
        $this->em = $this->container
            ->get('doctrine')
            ->getManager();
        $this->encoder = $this->container
            ->get('security.encoder_factory');
        $this->client = $this->getClient();

        $fixtures = array();
        $mauticBundles = $this->container->getParameter('mautic.bundles');
        foreach ($mauticBundles as $bundle) {
            //parse the namespace into a filepath
            $fixturesDir    = $bundle['directory'] . '/DataFixtures/ORM';

            if (file_exists($fixturesDir)) {
                //get files within the directory
                $iterator = new \FilesystemIterator($fixturesDir);
                //filter out inappropriate files
                $filter = new \RegexIterator($iterator, '/.php$/');
                if (iterator_count($filter)) {
                    foreach ($filter as $file) {
                        //add the file to be loaded
                        $class = str_replace(".php", "", $file->getFilename());
                        $fixtures[] = 'Mautic\\'.$bundle['bundle'].'\\DataFixtures\\ORM\\' . $class;
                    }
                }
            }
        }

        $this->loadFixtures($fixtures);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}
