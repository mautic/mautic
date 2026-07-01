<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar as GuzzleCookieJar;
use GuzzleHttp\RequestOptions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SamlTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDiscoveryTemplateIsOverridden(): void
    {
        $twig    = static::getContainer()->get('twig');
        $content = $twig->render('@LightSamlSp/discovery.html.twig', ['parties' => []]);

        Assert::assertStringContainsString('SAML not configured or configured incorrectly.', $content);
    }

    /**
     * An automatic tests implementation of SAML login.
     *
     * @see https://github.com/mautic/mautic/pull/13742#issue-2291225011
     */
    public function testSamlLogin(): void
    {
        if (!isset($_ENV['SAML_PORT'])) {
            // The env variable SAML_PORT. can be set in phpunit.xml
            $this->markTestSkipped('You can run this test when you set the SAML test docker image port.');
        }

        $attributeName = 'email';

        $host          = $_ENV['SAML_HOST'];
        $port          = $_ENV['SAML_PORT'];
        $temporaryFile = tempnam(sys_get_temp_dir(), 'samlTest');
        Assert::assertNotFalse($temporaryFile);

        // Go to http://localhost:8080/simplesaml/saml2/idp/metadata.php?output=xhtml and copy the metadata in xml format to a temp file (e.g. mautic_saml_test_metatada.xml).
        $metadataUrl = 'http://'.$host.':'.$port.'/simplesaml/saml2/idp/metadata.php';
        Assert::assertTrue(copy($metadataUrl, $temporaryFile), 'Error copying '.$metadataUrl.' to '.$temporaryFile);

        $configUrl        = '/s/config/edit?tab=userconfig';
        $crawler          = $this->client->request(Request::METHOD_GET, $configUrl);
        $configSaveButton = $crawler->selectButton('config[buttons][apply]');
        $configForm       = $configSaveButton->form();

        // Select your entity ID
        $samlIdField = $configForm['config[userconfig][saml_idp_entity_id]'];
        $this->assertInstanceOf(ChoiceFormField::class, $samlIdField);
        $availableSamlIdOptions = $samlIdField->availableOptionValues();
        Assert::assertCount(2, $availableSamlIdOptions, print_r($availableSamlIdOptions, true));
        $samlIdField->setValue($availableSamlIdOptions[1]);

        $samlDefaultRoleField = $configForm['config[userconfig][saml_idp_default_role]'];
        $this->assertInstanceOf(ChoiceFormField::class, $samlDefaultRoleField);
        $availableDefaultRoleOptions = $samlDefaultRoleField->availableOptionValues();
        Assert::assertCount(3, $availableDefaultRoleOptions, print_r($availableDefaultRoleOptions, true));
        $samlDefaultRoleField->setValue($availableDefaultRoleOptions[1]);

        // Upload the metadata.xml file
        $samlProviderMetadata = $configForm['config[userconfig][saml_idp_metadata]'];
        $this->assertInstanceOf(FileFormField::class, $samlProviderMetadata);
        $samlProviderMetadata->upload($temporaryFile);

        // Fill in email in the fields Email, First Name and Last name (we are testing, so no problem that the actual values are not correct)
        $configForm->setValues([
            'config[leadconfig][contact_columns]'               => ['name', 'email', 'id'],
            'config[companyconfig][company_columns]'            => ['companyname', 'companyemail', 'companywebsite', 'score', 'leadcount', 'id'],
            'config[userconfig][saml_idp_email_attribute]'      => $attributeName,
            'config[userconfig][saml_idp_firstname_attribute]'  => $attributeName,
            'config[userconfig][saml_idp_lastname_attribute]'   => $attributeName,
        ]);
        $this->client->submit($configForm);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $content = $clientResponse->getContent();
        Assert::assertNotFalse($content);
        Assert::assertStringContainsString('Configuration successfully updated', $content);

        // Go in an anonymous tab to <LOCAL_MAUTIC_URL> and you should be redirected to the simplesaml page
        $this->client->enableReboot();

        // Disable the default logging in via username and password.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);
        // Need to turn off redirects to catch SAML requests.
        $this->client->followRedirects(false);

        // Login with username user1 and password password
        $this->client->request(Request::METHOD_GET, '/s/dashboard');
        $clientResponse = $this->client->getResponse();
        // The request is going straight to discovery, thus sparing one redirect, that otherwise would be done anyway.
        // @see \LightSaml\SpBundle\Controller\DefaultController::loginAction if 'idp' is empty.
        self::assertResponseRedirects('/saml/discovery');
        $this->client->followRedirect();

        $clientResponse = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $clientResponse->getContent());
        self::assertResponseRedirects('/s/saml/login?idp=http://'.$host.':'.$port.'/simplesaml/saml2/idp/metadata.php');
        $this->client->followRedirect();

        $clientResponse = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $clientResponse->getContent());
        Assert::assertStringContainsString('http://'.$host.':'.$port.'/simplesaml/saml2/idp/SSOService.php?SAMLRequest=', $clientResponse->headers->get('Location'));

        // Here need to replicate the browser. The default client will not work, because the test must request an external service.
        $url          = $clientResponse->headers->get('Location');
        $cookieJar    = new GuzzleCookieJar();
        $guzzleClient = new Client([
            RequestOptions::COOKIES         => $cookieJar,
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);
        $response = $guzzleClient->request(Request::METHOD_GET, $url);
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
        Assert::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $url = $response->getHeaderLine('Location');
        Assert::assertStringContainsString('http://'.$host.':'.$port.'/simplesaml/module.php/core/loginuserpass.php?AuthState=', $url);

        // Get the form for authentication.
        $response = $guzzleClient->request(Request::METHOD_GET, $url);
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body    = (string) $response->getBody();
        $crawler = new Crawler($body, $url);
        $form    = $crawler->filter('form');
        Assert::assertCount(1, $form);

        $form = $form->form([
            'username' => 'user1',
            'password' => 'password',
        ]);
        $response = $guzzleClient->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'form_params' => $form->getPhpValues(),
            ]
        );
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
        // Because guzzle does not support javascript the answer is a form.
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body        = (string) $response->getBody();
        $crawler     = new Crawler($body, $url);
        $formElement = $crawler->filter('form');
        Assert::assertCount(1, $formElement);
        $form = $formElement->form();
        Assert::assertSame('https://localhost/s/saml/login_check', $form->getUri());
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
        );
        $clientResponse = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $clientResponse->getContent());
        self::assertResponseRedirects('https://localhost/s/dashboard');

        $this->client->followRedirect();
        $clientResponse = $this->client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $clientResponse->getContent());
        self::assertResponseRedirects('/s/dashboard');

        $this->client->followRedirect();
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $content = $clientResponse->getContent();
        Assert::assertNotFalse($content);
        Assert::assertStringContainsString('user1@example.com user1@example.com', $content);
    }
}
