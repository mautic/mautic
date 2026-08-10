<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\ErrorHandler;

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use PHPUnit\Framework\TestCase;

final class ErrorHandlerTest extends TestCase
{
    private string $originalCwd;

    private ?ErrorHandler $originalHandler = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalCwd     = getcwd() ?: sys_get_temp_dir();
        $this->originalHandler = ErrorHandler::$handler;
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        // register() assigns the static ErrorHandler::$handler singleton; restore
        // it so this test cannot leak handler state into later tests.
        ErrorHandler::$handler = $this->originalHandler;

        parent::tearDown();
    }

    /**
     * Regression test for https://github.com/mautic/mautic/issues/15873.
     *
     * On hosts where the PHP process working directory does not match the
     * Mautic install root (e.g. a cPanel PHP-FPM worker that starts in
     * /home/<user>/ instead of /home/<user>/public_html/), the FilesystemLoader
     * used by the error page must still be able to find the Offline / Exception
     * Twig templates. Previously the loader was constructed with relative paths
     * which resolved against the working directory, producing the misleading
     * "app/bundles/CoreBundle/Resources/views/Offline" directory does not exist
     * error in place of the rendered offline page.
     */
    public function testOfflineTemplateIsRenderedRegardlessOfWorkingDirectory(): void
    {
        // The regression happens in the production error-page code path. Under
        // PHPUnit, IS_PHPUNIT prevents register() from installing global PHP
        // handlers, so this configures the handler without leaking that state.
        ErrorHandler::register('prod');
        $handler = ErrorHandler::getHandler();
        $handler->setDisplayErrors(false);

        chdir(sys_get_temp_dir());

        $content = $handler->handleException(new \RuntimeException('boom'), true);

        self::assertIsString($content);
        // With the buggy relative paths, Twig's FilesystemLoader would have
        // thrown a LoaderError before any template was rendered, and the
        // catch block in generateResponse would have returned that message.
        self::assertStringContainsString('<!DOCTYPE html>', $content);
        self::assertStringContainsString('<div class="container">', $content);
        self::assertStringContainsString('<div class="alert alert-danger">', $content);
        self::assertStringContainsString('<div id="previous"></div>', $content);
        self::assertStringNotContainsString('Twig\\Error\\LoaderError', $content);
        self::assertStringNotContainsString('directory does not exist', $content);
    }
}
