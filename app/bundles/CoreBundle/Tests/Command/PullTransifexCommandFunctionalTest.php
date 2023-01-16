<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Command\PullTransifexCommand;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PullTransifexCommandFunctionalTest extends MauticMysqlTestCase
{
    private const FAKE_TRANSLATION_DIR = __DIR__.'/../Fixtures/Transifex/Translations';
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->configParams['transifex_api_token'] = 'some_api_token';

        parent::setUp();

        $this->filesystem = self::$container->get('mautic.filesystem');
        $this->filesystem->mkdir(self::FAKE_TRANSLATION_DIR);
    }

    public function testPullCommand(): void
    {
        Assert::assertFalse($this->filesystem->exists(self::FAKE_TRANSLATION_DIR.'/cs'), 'Translations directory already exist');

        // Using the same translation for both file as we don't know which response will be processed first.
        $someTranslation = 'some.translation="Some translation"';

        $handlerStack = self::$container->get('mautic.http.client.mock_handler');
        \assert($handlerStack instanceof MockHandler);
        $handlerStack->append(
            // Fetches all languages for webhook's messages.ini
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../Fixtures/Transifex/language-stats.json')),
            // Creates the download request for webhook's messages.ini
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../Fixtures/Transifex/translation-download.json')),
            // Fetches all languages for webhook's flashes.ini
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../Fixtures/Transifex/language-stats.json')),
            // Creates the download request for webhook's flashes.ini
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../Fixtures/Transifex/translation-download.json')),
            // Fetches the webhook's messages.ini content
            new Response(SymfonyResponse::HTTP_OK, [], $someTranslation),
            // Fetches the webhook's flashes.ini content
            new Response(SymfonyResponse::HTTP_OK, [], $someTranslation),
        );

        $commandTester = $this->testSymfonyCommand(PullTransifexCommand::NAME, ['--bundle' => 'WebhookBundle', '--language' => 'cs', '--path' => realpath(self::FAKE_TRANSLATION_DIR)]);

        Assert::assertSame(0, $commandTester->getStatusCode(), $commandTester->getDisplay());
        Assert::assertTrue($this->filesystem->exists(self::FAKE_TRANSLATION_DIR.'/cs'));
        Assert::assertTrue($this->filesystem->exists(self::FAKE_TRANSLATION_DIR.'/cs/WebhookBundle/messages.ini'));
        Assert::assertTrue($this->filesystem->exists(self::FAKE_TRANSLATION_DIR.'/cs/WebhookBundle/flashes.ini'));
        Assert::assertSame($someTranslation, $this->filesystem->readFile(self::FAKE_TRANSLATION_DIR.'/cs/WebhookBundle/messages.ini'));
        Assert::assertSame($someTranslation, $this->filesystem->readFile(self::FAKE_TRANSLATION_DIR.'/cs/WebhookBundle/flashes.ini'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove(self::FAKE_TRANSLATION_DIR);
    }
}
