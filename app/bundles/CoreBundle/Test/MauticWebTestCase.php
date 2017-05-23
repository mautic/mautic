<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test;

//@TODO - fix entity detachment issue that is leading to failed tests

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

define('MAUTIC_TEST_ENV', 1);

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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var bool
     */
    protected static $dbBooted;

    /**
     * @var ReferenceRepository
     */
    public $fixtures;

    protected function getClient(array $options = [], array $server = [])
    {
        if (empty($server['PHP_AUTH_USER'])) {
            $server['PHP_AUTH_USER'] = 'admin';
            $server['PHP_AUTH_PW']   = 'mautic';
        }
        $client = static::createClient($options, $server);

        $client->followRedirects(true);

        return $client;
    }

    protected function getNonAdminClient($user = 'sales', array $options = [], array $server = [])
    {
        if (empty($server['PHP_AUTH_USER'])) {
            $server['PHP_AUTH_USER'] = $user;
            $server['PHP_AUTH_PW']   = 'mautic';
        }
        $client = static::createClient($options, $server);

        return $client;
    }

    protected function getAnonClient(array $options = [], array $server = [])
    {
        $client = static::createClient($options, $server);

        $client->followRedirects(true);

        return $client;
    }

    protected function getOAuthAccessToken($fullTest = false)
    {
        $anonClient = $this->getAnonClient();

        $anonClient->followRedirects(false);

        $client       = $this->em->getRepository('MauticApiBundle:oAuth2\Client')->findOneByName('Mautic');
        $redirectUris = $client->getRedirectUris();
        $redirectUri  = urlencode($redirectUris[0]);
        $anonClient->request('GET', 'oauth/v2/auth?client_id='.$client->getPublicId().'&response_type=code&redirect_uri='.$redirectUri);
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
            [
                '_username' => 'admin',
                '_password' => 'mautic',
            ]
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
            $this->assertTrue($authorize, 'Could not find the authorization form.');
        }

        //should get a redirect header
        $location = $anonClient->getResponse()->headers->get('location');
        $this->assertTrue(!empty($location));

        //get the code
        $code = str_replace($redirectUris[0].'?code=', '', $location);
        $this->assertTrue(!empty($code));

        //reset the client
        $anonClient = $this->getAnonClient();

        //submit for a token
        $anonClient->request('GET', 'oauth/v2/token?client_id='.$client->getPublicId().'&client_secret='.
            $client->getSecret().'&grant_type=authorization_code&redirect_uri='.$redirectUri.
            '&code='.$code);

        $this->assertNoError($anonClient->getResponse(), $crawler);

        //get the access token
        $response = $anonClient->getResponse();
        $this->assertNoError($response, $crawler);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertTrue(!empty($decoded['access_token']));

        return $decoded['access_token'];
    }

    protected function assertContentType($response, $type = 'application/json')
    {
        $this->assertTrue(
            $response->headers->contains('Content-Type', $type), 'Unrecognized content type. Expecting '.$type.
            '; received '.$response->headers->get('Content-Type')
        );
    }

    protected function assertNoError($response, $crawler, $fullOutput = false)
    {
        $noException = true;
        $msg         = 'Status code '.$response->getStatusCode();
        if ($response->getStatusCode() >= 400) {
            //symfony inserts the exception into the title so extract it from that
            if (count($crawler)) {
                $msg .= ': '.trim($crawler->filter('title')->text());
            } elseif ($response->getContent()) {
                if ($response->headers->get('Content-Type') == 'application/json') {
                    $content = json_decode($response->getContent());
                    if ($fullOutput) {
                        $message = print_r($content, true);
                    } elseif (is_array($content) && (isset($content[0]) && is_object($content[0]))) {
                        $message = (isset($content[0]->message)) ? $content[0]->message : '';
                    } elseif (is_object($content)) {
                        $message = $content->message;
                    } else {
                        $message = print_r($content, true);
                    }
                    $msg .= ': '.$message;
                } else {
                    $msg .= ': '.$response->getContent();
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
        $request      = Request::createFromGlobals();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $this->container->set('request_stack', $requestStack);

        //setup the entity manager
        $this->em = $this->container
            ->get('doctrine')->getManager();
        $this->encoder = $this->container
            ->get('security.encoder_factory');

        $this->setupDatabaseOnFirstRun();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        if ($this->em) {
            $this->em->close();
        }
        unset($this->em, $this->container, $this->client, $this->encoder);
    }

    /**
     * TODO: Backup the database after creation, and store in cache dir to reimport between test.
     */
    private function setupDatabaseOnFirstRun()
    {
        $this->runCommand('doctrine:schema:drop', [
            '--env'   => 'test',
            '--force' => true,
            '--quiet' => true,
        ], true);

        $this->runCommand('doctrine:schema:create', [
            '--env'   => 'test',
            '--quiet' => true,
        ], true);

        $this->em->getConnection()->query('SET GLOBAL FOREIGN_KEY_CHECKS = 0;');

        $this->fixtures = $this->loadFixtures($this->getMauticFixtures(true))->getReferenceRepository();

        $this->em->getConnection()->query('SET GLOBAL FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Returns Mautic fixtures.
     *
     * @param bool $returnClassNames
     *
     * @return array
     */
    private function getMauticFixtures($returnClassNames = false)
    {
        $fixtures      = [];
        $mauticBundles = $this->container->getParameter('mautic.bundles');
        foreach ($mauticBundles as $bundle) {
            $fixturesDir = $bundle['directory'].'/DataFixtures/ORM';

            if (file_exists($fixturesDir)) {
                $classPrefix = 'Mautic\\'.$bundle['bundle'].'\\DataFixtures\\ORM\\';
                $this->populateFixturesFromDirectory($fixturesDir, $fixtures, $classPrefix, $returnClassNames);
            }

            $testFixturesDir = $bundle['directory'].'/Tests/DataFixtures/ORM';

            if (MAUTIC_TEST_ENV && file_exists($testFixturesDir)) {
                $classPrefix = 'Mautic\\'.$bundle['bundle'].'\\Tests\\DataFixtures\\ORM\\';
                $this->populateFixturesFromDirectory($testFixturesDir, $fixtures, $classPrefix, $returnClassNames);
            }
        }

        return $fixtures;
    }

    /**
     * @param string $fixturesDir
     * @param array  $fixtures
     * @param string $classPrefix
     * @param bool   $returnClassNames
     */
    private function populateFixturesFromDirectory($fixturesDir, array &$fixtures, $classPrefix = null, $returnClassNames = false)
    {
        if ($returnClassNames) {
            //get files within the directory
            $finder = new Finder();
            $finder->files()->in($fixturesDir)->name('*.php');
            foreach ($finder as $file) {
                //add the file to be loaded
                $class      = str_replace('.php', '', $file->getFilename());
                $fixtures[] = $classPrefix.$class;
            }
        } else {
            $fixtures[] = $fixturesDir;
        }
    }
}
