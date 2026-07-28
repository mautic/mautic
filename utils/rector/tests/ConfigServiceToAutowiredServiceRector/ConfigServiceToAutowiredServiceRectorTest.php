<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector;

use PhpParser\Node\Stmt\Return_;
use Rector\Application\Provider\CurrentFileProvider;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Parser\SimplePhpParser;
use Rector\PhpParser\Printer\BetterStandardPrinter;
use Rector\Testing\PHPUnit\AbstractLazyTestCase;
use Rector\ValueObject\Application\File;
use Utils\Rector\ConfigServiceToAutowiredServiceRector;

/**
 * The rule moves code between config.php and services.php of the very same directory,
 * so it needs both files on disk - that is beyond what plain *.php.inc fixtures can express.
 *
 * Only config.php is refactored, services.php is written by the rule itself.
 */
final class ConfigServiceToAutowiredServiceRectorTest extends AbstractLazyTestCase
{
    private const CONFIG_WITH_CLASS_ONLY_SERVICE = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.helper' => [
                'class' => SomeNamespace\SomeHelper::class,
            ],
            'mautic.some.wired_helper' => [
                'class'     => SomeNamespace\SomeWiredHelper::class,
                'arguments' => [
                    'translator',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

    private const SERVICES_FILE = <<<'CODE_SAMPLE'
<?php

return function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->load('SomeNamespace\\', '../');

    $services->alias('mautic.some.model', SomeNamespace\SomeModel::class);
};
CODE_SAMPLE;

    private const SERVICES_FILE_WITH_REGISTERED_SERVICE = <<<'CODE_SAMPLE'
<?php

return function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->load('SomeNamespace\\', '../');

    $services->set('mautic.some.helper', SomeNamespace\SomeHelper::class);

    $services->alias('mautic.some.model', SomeNamespace\SomeModel::class);
};
CODE_SAMPLE;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        // the container is shared between test cases, drop it to get a rule with a fresh file provider
        self::$rectorConfig = null;

        $this->temporaryDirectory = sys_get_temp_dir().'/rector_config_service_test_'.uniqid();
        mkdir($this->temporaryDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory.'/*.php') ?: [] as $filePath) {
            unlink($filePath);
        }

        rmdir($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testMovesClassOnlyServiceInSingleRun(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile(self::CONFIG_WITH_CLASS_ONLY_SERVICE);
        $this->assertIsString($printedFileContent);

        // the manually wired service stays in config.php
        $this->assertStringNotContainsString('mautic.some.helper', $printedFileContent);
        $this->assertStringContainsString('mautic.some.wired_helper', $printedFileContent);

        // the new service lands below loads and above aliases, the rest of the file is left alone
        $expectedServicesFileContent = <<<'CODE_SAMPLE'
<?php

return function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->load('SomeNamespace\\', '../');
    $services->set('mautic.some.helper', SomeNamespace\SomeHelper::class);

    $services->alias('mautic.some.model', SomeNamespace\SomeModel::class);
};
CODE_SAMPLE;

        $this->assertSame($expectedServicesFileContent, $this->readServicesFile());
    }

    public function testRemovesGroupLeftWithoutService(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'permissions' => [
            'mautic.some.helper' => [
                'class' => SomeNamespace\SomeHelper::class,
            ],
        ],
        'others' => [
            'mautic.some.wired_helper' => [
                'class'     => SomeNamespace\SomeWiredHelper::class,
                'arguments' => [
                    'translator',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile($configFileContent);
        $this->assertIsString($printedFileContent);

        // the group has no service left, the one that kept its service stays
        $this->assertStringNotContainsString('permissions', $printedFileContent);
        $this->assertStringContainsString("'others'", $printedFileContent);
        $this->assertStringContainsString('mautic.some.wired_helper', $printedFileContent);
    }

    public function testRemovesServicesLeftWithoutGroup(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'routes' => [
        'main' => [
            'mautic_some_index' => [
                'path' => '/some/{page}',
            ],
        ],
    ],
    'services' => [
        'permissions' => [
            'mautic.some.helper' => [
                'class' => SomeNamespace\SomeHelper::class,
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile($configFileContent);
        $this->assertIsString($printedFileContent);

        $this->assertStringNotContainsString('services', $printedFileContent);
        $this->assertStringContainsString('mautic_some_index', $printedFileContent);
    }

    public function testMovesServiceBelowLastStatementOfLoadLessServicesFile(): void
    {
        $servicesFileContent = <<<'CODE_SAMPLE'
<?php

return function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->alias('mautic.some.model', SomeNamespace\SomeModel::class);
};
CODE_SAMPLE;

        $this->createFile('services.php', $servicesFileContent);

        $this->assertIsString($this->refactorConfigFile(self::CONFIG_WITH_CLASS_ONLY_SERVICE));

        $expectedServicesFileContent = <<<'CODE_SAMPLE'
<?php

return function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->alias('mautic.some.model', SomeNamespace\SomeModel::class);
    $services->set('mautic.some.helper', SomeNamespace\SomeHelper::class);
};
CODE_SAMPLE;

        $this->assertSame($expectedServicesFileContent, $this->readServicesFile());
    }

    public function testMovesServiceWithAutowirableArguments(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.autowirable_helper' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'arguments' => [
                    'translator',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile($configFileContent);
        $this->assertIsString($printedFileContent);
        $this->assertStringNotContainsString('mautic.some.autowirable_helper', $printedFileContent);

        $this->assertStringContainsString(
            "\$services->set('mautic.some.autowirable_helper', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class);",
            $this->readServicesFile()
        );
    }

    public function testSkipServiceWithScalarConstructorArgument(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.parameter_aware_helper' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ParameterAwareHelper::class,
                'arguments' => [
                    'translator',
                    '%mautic.some_secret%',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertNull($this->refactorConfigFile($configFileContent));
        $this->assertSame(self::SERVICES_FILE, $this->readServicesFile());
    }

    public function testSkipServiceWithUnknownClass(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.wired_helper' => [
                'class'     => SomeNamespace\SomeWiredHelper::class,
                'arguments' => [
                    'translator',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertNull($this->refactorConfigFile($configFileContent));
        $this->assertSame(self::SERVICES_FILE, $this->readServicesFile());
    }

    public function testRemovesAlreadyRegisteredServiceWithoutDuplicatingIt(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE_WITH_REGISTERED_SERVICE);

        $printedFileContent = $this->refactorConfigFile(self::CONFIG_WITH_CLASS_ONLY_SERVICE);
        $this->assertIsString($printedFileContent);

        $this->assertStringNotContainsString('mautic.some.helper', $printedFileContent);
        $this->assertStringContainsString('mautic.some.wired_helper', $printedFileContent);

        // the service is registered already, so services.php is left untouched
        $this->assertSame(self::SERVICES_FILE_WITH_REGISTERED_SERVICE, $this->readServicesFile());
    }

    public function testSkipConfigWithoutServicesFile(): void
    {
        $this->assertNull($this->refactorConfigFile(self::CONFIG_WITH_CLASS_ONLY_SERVICE));
    }

    public function testSkipNonAutowiredServicesFile(): void
    {
        $servicesFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'some' => 'array',
];
CODE_SAMPLE;

        $this->createFile('services.php', $servicesFileContent);

        $this->assertNull($this->refactorConfigFile(self::CONFIG_WITH_CLASS_ONLY_SERVICE));
        $this->assertSame($servicesFileContent, $this->readServicesFile());
    }

    public function testMovesServiceWithSingleTag(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.voter' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'arguments' => [
                    'mautic.security',
                ],
                'tag' => 'security.voter',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $this->assertStringContainsString(
            "\$services->set('mautic.some.voter', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class)->tag('security.voter');",
            $this->readServicesFile()
        );
    }

    public function testMovesServiceWithTagsAndTagArguments(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.event_listener' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'arguments' => [
                    'translator',
                ],
                'tags' => [
                    'kernel.event_listener',
                    'kernel.event_listener',
                ],
                'tagArguments' => [
                    [
                        'event'  => 'fos_oauth_server.pre_authorization_process',
                        'method' => 'onPreAuthorizationProcess',
                    ],
                    [
                        'event'  => 'fos_oauth_server.post_authorization_process',
                        'method' => 'onPostAuthorizationProcess',
                    ],
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $servicesFileContent = $this->readServicesFile();

        $this->assertStringContainsString(
            "->tag('kernel.event_listener', ['event' => 'fos_oauth_server.pre_authorization_process', 'method' => 'onPreAuthorizationProcess'])",
            $servicesFileContent
        );

        $this->assertStringContainsString(
            "->tag('kernel.event_listener', ['event' => 'fos_oauth_server.post_authorization_process', 'method' => 'onPostAuthorizationProcess'])",
            $servicesFileContent
        );
    }

    public function testSkipServiceWithAlias(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.translation_loader' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'tag'   => 'translation.loader',
                'alias' => 'mautic',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertNull($this->refactorConfigFile($configFileContent));
        $this->assertSame(self::SERVICES_FILE, $this->readServicesFile());
    }

    public function testSkipConfigWithoutServices(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE);

        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'routes' => [
        'main' => [
            'mautic_some_index' => [
                'path' => '/some/{page}',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->assertNull($this->refactorConfigFile($configFileContent));
        $this->assertSame(self::SERVICES_FILE, $this->readServicesFile());
    }

    public function testSkipThirdPartyClassOverride(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'oneup_uploader.controller.dropzone.class' => [
                'class' => SomeNamespace\UploadController::class,
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertNull($this->refactorConfigFile($configFileContent));
        $this->assertSame(self::SERVICES_FILE, $this->readServicesFile());
    }

    private function createFile(string $fileName, string $fileContent): string
    {
        $filePath = $this->temporaryDirectory.'/'.$fileName;
        file_put_contents($filePath, $fileContent);

        return $filePath;
    }

    private function readServicesFile(): string
    {
        $servicesFileContent = file_get_contents($this->temporaryDirectory.'/services.php');
        $this->assertIsString($servicesFileContent);

        return $servicesFileContent;
    }

    /**
     * @return string|null printed config.php contents, null when the rule made no change
     */
    private function refactorConfigFile(string $fileContent): ?string
    {
        $filePath = $this->createFile('config.php', $fileContent);

        $currentFileProvider = new CurrentFileProvider();
        $currentFileProvider->setFile(new File($filePath, $fileContent));

        // must be shared, so the rule under test picks the very same file
        self::getContainer()->instance(CurrentFileProvider::class, $currentFileProvider);

        $simplePhpParser = $this->make(SimplePhpParser::class);
        $stmts           = $simplePhpParser->parseString($fileContent);

        $betterNodeFinder = $this->make(BetterNodeFinder::class);
        $return           = $betterNodeFinder->findFirstInstanceOf($stmts, Return_::class);
        $this->assertInstanceOf(Return_::class, $return);

        $rector = $this->make(ConfigServiceToAutowiredServiceRector::class);

        if (!$rector->refactor($return) instanceof Return_) {
            return null;
        }

        $betterStandardPrinter = $this->make(BetterStandardPrinter::class);

        return $betterStandardPrinter->print($stmts);
    }
}
