<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

final class OpenApiDocumentationTest extends MauticMysqlTestCase
{
    public function testJsonAndYamlDocumentationExposeValidContract(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/v2/docs.jsonopenapi');
        self::assertResponseIsSuccessful();
        $jsonDocument = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(MAUTIC_VERSION, $jsonDocument['info']['version']);
        $this->assertSame('https://localhost', $jsonDocument['servers'][0]['url']);
        $this->assertSame('oauth2', $jsonDocument['components']['securitySchemes']['oauth2']['type']);
        $this->assertSame('basic', $jsonDocument['components']['securitySchemes']['basicAuth']['scheme']);
        $this->assertContains(['oauth2' => []], $jsonDocument['security']);
        $this->assertContains(['basicAuth' => []], $jsonDocument['security']);

        $this->assertDynamicContentEnums($jsonDocument);

        $this->client->request(Request::METHOD_GET, '/api/v2/docs.yamlopenapi');
        self::assertResponseIsSuccessful();
        $yaml = $this->client->getResponse()->getContent();
        $yamlDocument = Yaml::parse($yaml);

        $this->assertStringContainsString('parameters: []', (string) $yaml);
        $this->assertSame(MAUTIC_VERSION, $yamlDocument['info']['version']);
        $this->assertSame('https://localhost', $yamlDocument['servers'][0]['url']);
        $this->assertSame('oauth2', $yamlDocument['components']['securitySchemes']['oauth2']['type']);
        $this->assertSame('basic', $yamlDocument['components']['securitySchemes']['basicAuth']['scheme']);

        $this->assertDynamicContentEnums($yamlDocument);
    }

    /**
     * @param array<mixed> $document
     */
    private function assertDynamicContentEnums(array $document): void
    {
        $typeProperties = [];

        foreach ($document['components']['schemas'] as $schemaName => $schema) {
            if (str_starts_with($schemaName, 'DynamicContent') && isset($schema['properties']['type'])) {
                $typeProperties[$schemaName] = $schema['properties']['type'];
            }
        }

        foreach (['DynamicContent', 'DynamicContent-dynamicContent.read', 'DynamicContent-dynamicContent.write'] as $schemaName) {
            $this->assertArrayHasKey($schemaName, $typeProperties);
        }

        foreach ($typeProperties as $schemaName => $typeProperty) {
            $this->assertSame(['html', 'text'], $typeProperty['enum'] ?? null, sprintf('Invalid enum in schema "%s".', $schemaName));
        }
    }
}
