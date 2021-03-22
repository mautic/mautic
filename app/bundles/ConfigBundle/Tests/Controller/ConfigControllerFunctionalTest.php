<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ConfigControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['config_allowed_parameters'] = [
            'kernel.root_dir',
            'kernel.project_dir',
        ];

        parent::setUp();

        if (file_exists($this->getConfigPath())) {
            copy($this->getConfigPath(), $this->getConfigPath().'.backup');
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->getConfigPath().'.backup')) {
            rename($this->getConfigPath().'.backup', $this->getConfigPath());
        } else {
            // local.php didn't exist to start with so delete as some test CI use environment variables
            unlink($this->getConfigPath());
        }

        parent::tearDown();
    }

    public function testValuesAreEscapedProperly(): void
    {
        $url             = 'https://test.us/create?key=2MLzQFXBSqd2nqwGero90CpB1jX1FbVhhRd51ojr&domain=https%3A%2F%2Ftest.us%2F&longUrl=';
        $trackIps        = "%ip1%\n%ip2%\n%kernel.root_dir%\n%kernel.project_dir%";
        $googleAnalytics = 'reveal pass: %mautic.db_password%';

        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');

        // Find save & close button
        $buttonCrawler  = $crawler->selectButton('config[buttons][save]');
        $form           = $buttonCrawler->form();
        $form->setValues([
            'config[coreconfig][link_shortener_url]' => $url,
            'config[coreconfig][do_not_track_ips]'   => $trackIps,
            'config[pageconfig][google_analytics]'   => $googleAnalytics,
            'config[leadconfig][contact_columns]'    => ['name', 'email', 'id'],
        ]);

        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        // Check values are unescaped properly in the edit form
        $crawler        = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $buttonCrawler  =  $crawler->selectButton('config[buttons][save]');
        $form           = $buttonCrawler->form();
        Assert::assertEquals($url, $form['config[coreconfig][link_shortener_url]']->getValue());
        Assert::assertEquals($trackIps, $form['config[coreconfig][do_not_track_ips]']->getValue());
        Assert::assertEquals($googleAnalytics, $form['config[pageconfig][google_analytics]']->getValue());

        // Check values are escaped properly in the config file
        $configParameters = $this->getConfigParameters();
        Assert::assertArrayHasKey('link_shortener_url', $configParameters);
        Assert::assertSame($this->escape($url), $configParameters['link_shortener_url']);
        Assert::assertArrayHasKey('do_not_track_ips', $configParameters);
        Assert::assertSame([
            $this->escape('%ip1%'),
            $this->escape('%ip2%'),
            '%kernel.root_dir%',
            '%kernel.project_dir%',
        ], $configParameters['do_not_track_ips']);
        Assert::assertArrayHasKey('google_analytics', $configParameters);
        Assert::assertSame($this->escape($googleAnalytics), $configParameters['google_analytics']);
    }

    private function getConfigPath(): string
    {
        return $this->container->getParameter('kernel.project_dir').'/app/config/local.php';
    }

    private function getConfigParameters(): array
    {
        include $this->getConfigPath();

        /* @var array $parameters */
        return $parameters;
    }

    private function escape(string $value): string
    {
        return str_replace('%', '%%', $value);
    }
}
