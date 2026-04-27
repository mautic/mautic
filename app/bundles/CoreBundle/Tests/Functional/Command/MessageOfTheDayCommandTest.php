<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;

final class MessageOfTheDayCommandTest extends MauticMysqlTestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $cachePath = tempnam(sys_get_temp_dir(), 'motd_');

        if (false === $cachePath) {
            self::fail('Unable to create a temporary cache file.');
        }

        if (!unlink($cachePath)) {
            self::fail('Unable to reset the temporary cache file.');
        }

        $this->cachePath = $cachePath;

        $this->configParams['motd_url']        = 'https://example.com/motd.json';
        $this->configParams['motd_cache_path'] = $this->cachePath;
        $this->configParams['motd_cache_ttl']  = 3600;

        parent::setUp();
    }

    #[After]
    public function cleanupMotdCacheFile(): void
    {
        if (isset($this->cachePath) && is_file($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('provideCachedMotdPayloads')]
    public function testItReadsAndRendersCachedMotd(array $payload, bool $expectMessage, string $expectedCategory, string $expectedContent): void
    {
        $json = (string) json_encode($payload, JSON_THROW_ON_ERROR);

        file_put_contents($this->cachePath, $json);

        $tester = $this->testSymfonyCommand('mautic:motd');

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $display = $tester->getDisplay();

        if ($expectMessage) {
            $this->assertStringContainsString($expectedCategory, $display);
            $this->assertStringContainsString($expectedContent, $display);

            return;
        }

        $this->assertStringNotContainsString($expectedCategory, $display);
        $this->assertStringNotContainsString($expectedContent, $display);
    }

    /**
     * @return iterable<string, array{
     *     0: array<string, mixed>,
     *     1: bool,
     *     2: string,
     *     3: string
     * }>
     */
    public static function provideCachedMotdPayloads(): iterable
    {
        yield 'timeless message for cli channel' => [
            [
                'categories' => [
                    'news' => ['label' => 'News'],
                ],
                'messages' => [
                    [
                        'category' => 'news',
                        'content'  => [
                            'cli' => ['Welcome to Mautic'],
                        ],
                        'start' => null,
                        'end'   => null,
                    ],
                ],
            ],
            true,
            'News',
            'Welcome to Mautic',
        ];

        yield 'timed message within active window' => [
            [
                'categories' => [
                    'news' => ['label' => 'News'],
                ],
                'messages' => [
                    [
                        'category' => 'news',
                        'content'  => [
                            'cli' => ['Active timed message'],
                        ],
                        'start' => (new \DateTime('-1 day'))->format('c'),
                        'end'   => (new \DateTime('+1 day'))->format('c'),
                    ],
                ],
            ],
            true,
            'News',
            'Active timed message',
        ];

        yield 'expired timed message is ignored' => [
            [
                'categories' => [
                    'news' => ['label' => 'News'],
                ],
                'messages' => [
                    [
                        'category' => 'news',
                        'content'  => [
                            'cli' => ['Expired timed message'],
                        ],
                        'start' => (new \DateTime('-3 days'))->format('c'),
                        'end'   => (new \DateTime('-1 day'))->format('c'),
                    ],
                ],
            ],
            false,
            'News',
            'Expired timed message',
        ];

        yield 'message without cli channel is ignored' => [
            [
                'categories' => [
                    'news' => ['label' => 'News'],
                ],
                'messages' => [
                    [
                        'category' => 'news',
                        'content'  => [
                            'ui' => ['Not for CLI'],
                        ],
                        'start' => null,
                        'end'   => null,
                    ],
                ],
            ],
            false,
            'News',
            'Not for CLI',
        ];
    }

    public function testItFailsWhenCachedMotdJsonIsInvalid(): void
    {
        file_put_contents($this->cachePath, '{invalid-json');

        $tester = $this->testSymfonyCommand('mautic:motd');

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Could not decode MOTD JSON', $tester->getDisplay());
    }
}
