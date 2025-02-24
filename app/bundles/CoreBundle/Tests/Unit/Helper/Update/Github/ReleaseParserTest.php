<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\Update\Github;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Helper\Update\Exception\LatestVersionSupportedException;
use Mautic\CoreBundle\Helper\Update\Github\ReleaseParser;
use PHPUnit\Framework\TestCase;

class ReleaseParserTest extends TestCase
{
    private ReleaseParser $releaseParser;

    protected function setUp(): void
    {
        $client = new Client(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) {
                            $metadata = file_get_contents(__DIR__.'/json/metadata-2.16.0.json');

                            return new Response(200, [], $metadata);
                        },
                        function (Request $request, array $options) {
                            $metadata = file_get_contents(__DIR__.'/json/metadata-3.0.1-beta.json');

                            return new Response(200, [], $metadata);
                        },
                        function (Request $request, array $options) {
                            $metadata = file_get_contents(__DIR__.'/json/metadata-3.0.1-alpha.json');

                            return new Response(200, [], $metadata);
                        },
                        function (Request $request, array $options) {
                            $metadata = file_get_contents(__DIR__.'/json/metadata-3.0.0.json');

                            return new Response(200, [], $metadata);
                        },
                        function (Request $request, array $options) {
                            $metadata = file_get_contents(__DIR__.'/json/metadata-2.15.0.json');

                            return new Response(200, [], $metadata);
                        },
                    ]
                ),
            ]
        );

        $this->releaseParser = new ReleaseParser($client);
    }

    public function testMatchingReleaseReturnedForAlphaStability(): void
    {
        $expects       = '3.0.1-beta';
        $mauticVersion = '3.0.0-alpha';
        $stability     = 'alpha';

        $release = $this->releaseParser->getLatestSupportedRelease($this->getReleases(), $mauticVersion, $stability);

        $this->assertSame($expects, $release->getVersion());
    }

    public function testMatchingReleaseReturnedForBetaStability(): void
    {
        $expects       = '3.0.1-beta';
        $mauticVersion = '3.0.0-alpha';
        $stability     = 'beta';

        $release = $this->releaseParser->getLatestSupportedRelease($this->getReleases(), $mauticVersion, $stability);

        $this->assertSame($expects, $release->getVersion());
    }

    public function testMatchingReleaseReturnedForStableStability(): void
    {
        $expects       = '3.0.0';
        $mauticVersion = '2.20.0';
        $stability     = 'stable';

        $release = $this->releaseParser->getLatestSupportedRelease($this->getReleases(), $mauticVersion, $stability);

        $this->assertSame($expects, $release->getVersion());
    }

    public function testMatchingReleaseReturnedForMinimumMauticVersion(): void
    {
        $expects       = '2.15.0';
        $mauticVersion = '2.1.0';
        $stability     = 'stable';

        $release = $this->releaseParser->getLatestSupportedRelease($this->getReleases(), $mauticVersion, $stability);

        $this->assertSame($expects, $release->getVersion());
    }

    public function testLatestVersionSupportedExceptionThrownIfMetadataErrors(): void
    {
        $this->expectException(LatestVersionSupportedException::class);

        $mauticVersion = '2.16.0';
        $stability     = 'stable';

        $client = new Client(
            [
                'handler' => new MockHandler(
                    [
                        fn (Request $request, array $options) => new Response(500),
                    ]
                ),
            ]
        );

        (new ReleaseParser($client))->getLatestSupportedRelease([['html_url' => 'foo://bar']], $mauticVersion, $stability);
    }

    public function testLatestVersionSupportedExceptionThrownIfMetadataNotFound(): void
    {
        $this->expectException(LatestVersionSupportedException::class);

        $mauticVersion = '2.16.0';
        $stability     = 'stable';

        $client = new Client(
            [
                'handler' => new MockHandler(
                    [
                        fn (Request $request, array $options) => new Response(200, [], json_encode(['foo' => 'bar'])),
                    ]
                ),
            ]
        );

        (new ReleaseParser($client))->getLatestSupportedRelease([['html_url' => 'foo://bar']], $mauticVersion, $stability);
    }

    private function getReleases(): array
    {
        return json_decode(file_get_contents(__DIR__.'/json/releases.json'), true);
    }
}
