<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Serializer;

use ApiPlatform\Serializer\YamlEncoder as ApiPlatformYamlEncoder;
use Mautic\ApiBundle\Serializer\OpenApiYamlEncoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

final class OpenApiYamlEncoderTest extends TestCase
{
    public function testPreservesEmptySequencesAndObjects(): void
    {
        $encoder = new OpenApiYamlEncoder(
            new ApiPlatformYamlEncoder('yamlopenapi', new YamlEncoder()),
        );

        $yaml = $encoder->encode([
            'security'   => [],
            'components' => new \ArrayObject(),
        ], 'yamlopenapi');

        $this->assertStringContainsString("\n", $yaml);
        $this->assertStringContainsString('security: []', $yaml);
        $this->assertMatchesRegularExpression('/components: \{\s*\}/', $yaml);
    }
}
