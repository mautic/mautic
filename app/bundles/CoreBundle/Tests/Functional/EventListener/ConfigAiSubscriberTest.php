<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ConfigAiSubscriberTest extends MauticMysqlTestCase
{
    private const string PAGE = '/s/config/edit/aiconfig';

    private const string KEY = 'fixture-ai-key-for-config-test';

    protected function setUp(): void
    {
        $this->configParams['secret_key'] = EncryptionHelper::generateKey();
        parent::setUp();
    }

    public function testAssignmentsAndEncryptedCredentialsCanBeSavedAndEdited(): void
    {
        $this->submit([
            'ai_providers' => [
                ['name' => 'Ollama', 'endpoint' => 'http://ollama:11434/v1', 'location' => 'local'],
                ['name' => 'Hosted', 'endpoint' => 'https://models.example.test/v1', 'location' => 'cloud', 'api_key' => self::KEY],
            ],
            'ai_routine_provider' => '0',
            'ai_routine_model'    => 'qwen:small',
            'ai_complex_provider' => '1',
            'ai_complex_model'    => 'org/large',
        ]);
        $config = $this->savedConfig();
        $sealed = $config['ai_providers'][1]['api_key'];
        $this->assertNotSame(self::KEY, $sealed);
        $this->assertSame(self::KEY, self::getContainer()->get(EncryptionHelper::class)->decrypt($sealed));
        $this->assertFalse($config['ai_allow_cloud']);
        $this->assertSame('0', $config['ai_routine_provider']);
        $this->assertSame('1', $config['ai_complex_provider']);

        $crawler = $this->client->request(Request::METHOD_GET, self::PAGE);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString(self::KEY, (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString($sealed, (string) $this->client->getResponse()->getContent());
        $this->assertSame('org/large', $crawler->filter('#config_aiconfig_ai_complex_model')->attr('value'));
        $this->assertCount(1, $crawler->filter('#config_aiconfig_ai_complex_provider option[value="1"][selected]'));

        $providers = [1 => ['name' => 'Hosted', 'endpoint' => 'https://models.example.test/v1', 'location' => 'cloud', 'api_key' => '']];
        $this->submit([
            'ai_providers'        => $providers,
            'ai_allow_cloud'      => '1',
            'ai_routine_provider' => '1',
            'ai_routine_model'    => 'org/large',
            'ai_complex_provider' => '1',
            'ai_complex_model'    => 'org/large',
        ]);
        $config = $this->savedConfig();
        $this->assertTrue($config['ai_allow_cloud']);
        $this->assertSame([1], array_keys($config['ai_providers']));
        $this->assertSame($sealed, $config['ai_providers'][1]['api_key']);
        $this->assertSame($config['ai_routine_provider'], $config['ai_complex_provider']);
        $this->assertSame($config['ai_routine_model'], $config['ai_complex_model']);

        $providers[1]['endpoint'] = 'https://other.example.test/v1';
        $this->submit(['ai_providers' => $providers]);
        $this->assertSame('', $this->savedConfig()['ai_providers'][1]['api_key']);

        $audit = implode(' ', $this->connection->fetchFirstColumn(sprintf('SELECT details FROM %saudit_log WHERE bundle = ?', MAUTIC_TABLE_PREFIX), ['config']));
        $this->assertStringContainsString('Hosted', $audit);
        $this->assertStringNotContainsString(self::KEY, $audit);
        $this->assertStringNotContainsString($sealed, $audit);
    }

    public function testInvalidProviderUrlAndIncompleteAssignmentAreNotSaved(): void
    {
        $before = $this->savedConfig();
        $this->submit([
            'ai_providers' => [['name' => 'Hosted', 'endpoint' => 'http://models.example.test/v1', 'location' => 'cloud', 'api_key' => self::KEY]],
            'ai_routine_provider' => '0',
        ]);
        $this->assertSame($before, $this->savedConfig());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('needs a valid base URL', $content);
        $this->assertStringContainsString('Choose both a provider and a model', $content);
        $this->assertStringNotContainsString(self::KEY, $content);
    }

    public function testInvalidCsrfTokenCannotSaveSettings(): void
    {
        $before = $this->savedConfig();
        $this->submit(['ai_allow_cloud' => '1'], 'invalid');
        $this->assertSame($before, $this->savedConfig());
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function submit(array $settings, ?string $csrf = null): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, self::PAGE);
        $this->assertResponseIsSuccessful();
        $form   = $crawler->selectButton('config[buttons][apply]')->form();
        $values = $form->getPhpValues();
        $values['config']['aiconfig'] = $settings;
        if (null !== $csrf) {
            $values['config']['_token'] = $csrf;
        }
        $this->client->request(Request::METHOD_POST, self::PAGE, $values);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return array<string, mixed>
     */
    private function savedConfig(): array
    {
        return new Configurator(self::getContainer()->get(PathsHelper::class))->getParameters();
    }
}
