<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;

/**
 * @see https://twig.symfony.com/doc/3.x/advanced.html#functional-tests
 */
abstract class AbstractTwigIntegrationTestCase extends TestCase
{
    /**
     * @return ExtensionInterface[]
     */
    abstract protected function getExtensions(): array;

    abstract protected static function getFixturesDirectory(): string;

    #[DataProvider('provideFixtures')]
    public function testIntegration(string $template, string $data, string $expected, string $message): void
    {
        $loader = new ArrayLoader(['index.twig' => $template]);
        $twig   = new Environment($loader, [
            'cache'            => false,
            'strict_variables' => true,
        ]);

        foreach ($this->getExtensions() as $extension) {
            $twig->addExtension($extension);
        }

        /** @var array<string, mixed> $context */
        $context = eval($data.';');
        $output  = trim($twig->render('index.twig', $context), "\n ");

        $this->assertSame(trim($expected, "\n "), $output, $message);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideFixtures(): iterable
    {
        $fixturesDir = realpath(static::getFixturesDirectory());

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fixturesDir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('test' !== $file->getExtension()) {
                continue;
            }

            $content = (string) file_get_contents($file->getRealPath());

            preg_match('/--TEST--\s*(.*?)\s*--TEMPLATE--\s*(.*?)\s*--DATA--\s*(.*?)\s*--EXPECT--\s*(.*)/s', $content, $match);

            $name = str_replace($fixturesDir.\DIRECTORY_SEPARATOR, '', $file->getRealPath());

            yield $name => [$match[2], $match[3], $match[4], $match[1]];
        }
    }
}
