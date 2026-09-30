<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\SAMLEnvVars;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

final class SAMLEnvVarsTest extends TestCase
{
    private ParameterBag $config;

    private ParameterBag $defaultConfig;

    private ParameterBag $envVars;

    protected function setUp(): void
    {
        $this->config        = new ParameterBag();
        $this->defaultConfig = new ParameterBag();
        $this->envVars       = new ParameterBag();
    }

    public function testEntityIdIsSetToConfigIfNotEmpty(): void
    {
        $this->config->set('saml_idp_entity_id', 'foobar');
        SAMLEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('foobar', $this->envVars->get('MAUTIC_SAML_ENTITY_ID'));
    }

    public function testEntityIdIsSetToSiteUrlIfNotEmpty(): void
    {
        $this->config->set('saml_idp_entity_id', '');
        $this->config->set('site_url', 'https://foobar.com/happydays');

        SAMLEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('https://foobar.com', $this->envVars->get('MAUTIC_SAML_ENTITY_ID'));
    }

    #[DataProvider('provideSiteUrlsWithoutAHost')]
    public function testEntityIdFallsBackWhenSiteUrlHasNoHost(string $siteUrl): void
    {
        $this->config->set('saml_idp_entity_id', '');
        $this->config->set('site_url', $siteUrl);

        SAMLEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('mautic', $this->envVars->get('MAUTIC_SAML_ENTITY_ID'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSiteUrlsWithoutAHost(): iterable
    {
        // parse_url() returns false for these.
        yield 'no host after the scheme' => ['http://'];
        yield 'empty authority'          => ['http:///example.com'];
        yield 'port without a host'      => ['https://:80'];

        // parse_url() succeeds for these but reports no host.
        yield 'bare word'                => ['mautic'];
        yield 'path only'                => ['/path/only'];
    }

    public function testEntityIdIsSetToMauticByDefault(): void
    {
        $this->config->set('saml_idp_entity_id', '');
        $this->config->set('site_url', '');

        SAMLEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('mautic', $this->envVars->get('MAUTIC_SAML_ENTITY_ID'));
    }

    public function testLoginPathIsDefaultIfSamlIsDisabled(): void
    {
        $this->config->set('saml_idp_metadata', 'enabled');

        SAMLEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('/s/saml/login', $this->envVars->get('MAUTIC_SAML_LOGIN_PATH'));
        $this->assertEquals('/s/saml/login_check', $this->envVars->get('MAUTIC_SAML_LOGIN_CHECK_PATH'));
    }

    public function testCorrectLoginPathIfSamlIsEnabled(): void
    {
        $this->config->set('saml_idp_metadata', '');

        SAMLEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('/s/saml/login', $this->envVars->get('MAUTIC_SAML_LOGIN_PATH'));
        $this->assertEquals('/s/saml/login_check', $this->envVars->get('MAUTIC_SAML_LOGIN_CHECK_PATH'));
    }
}
