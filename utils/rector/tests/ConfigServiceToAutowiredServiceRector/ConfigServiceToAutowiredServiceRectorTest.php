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

    public function testAddsClassOnlyServiceBelowLoad(): void
    {
        $this->createFile('config.php', self::CONFIG_WITH_CLASS_ONLY_SERVICE);

        $printedFileContent = $this->refactorFile('services.php', self::SERVICES_FILE);
        $this->assertIsString($printedFileContent);

        $this->assertStringContainsString(
            "\$services->set('mautic.some.helper', SomeNamespace\SomeHelper::class);",
            $printedFileContent
        );

        // the manually wired service stays in config.php
        $this->assertStringNotContainsString('mautic.some.wired_helper', $printedFileContent);

        // the new service lands below loads and above aliases
        $loadPosition  = strpos($printedFileContent, '$services->load');
        $setPosition   = strpos($printedFileContent, '$services->set');
        $aliasPosition = strpos($printedFileContent, '$services->alias');

        $this->assertGreaterThan($loadPosition, $setPosition);
        $this->assertGreaterThan($setPosition, $aliasPosition);
    }

    public function testAddsServiceWithAutowirableArguments(): void
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

        $this->createFile('config.php', $configFileContent);

        $printedFileContent = $this->refactorFile('services.php', self::SERVICES_FILE);
        $this->assertIsString($printedFileContent);

        $this->assertStringContainsString(
            "\$services->set('mautic.some.autowirable_helper', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class);",
            $printedFileContent
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

        $this->createFile('config.php', $configFileContent);

        $this->assertNull($this->refactorFile('services.php', self::SERVICES_FILE));
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

        $this->createFile('config.php', $configFileContent);

        $this->assertNull($this->refactorFile('services.php', self::SERVICES_FILE));
    }

    public function testRemovesAlreadyRegisteredServiceFromConfig(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE_WITH_REGISTERED_SERVICE);

        $printedFileContent = $this->refactorFile('config.php', self::CONFIG_WITH_CLASS_ONLY_SERVICE);
        $this->assertIsString($printedFileContent);

        $this->assertStringNotContainsString('mautic.some.helper', $printedFileContent);
        $this->assertStringContainsString('mautic.some.wired_helper', $printedFileContent);
    }

    public function testSkipServiceNotRegisteredInServicesFileYet(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertNull($this->refactorFile('config.php', self::CONFIG_WITH_CLASS_ONLY_SERVICE));
    }

    public function testSkipConfigWithoutServicesFile(): void
    {
        $this->assertNull($this->refactorFile('config.php', self::CONFIG_WITH_CLASS_ONLY_SERVICE));
    }

    public function testSkipServiceWithTag(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE_WITH_REGISTERED_SERVICE);

        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'events' => [
            'mautic.some.helper' => [
                'class' => SomeNamespace\SomeHelper::class,
                'tag'   => 'kernel.event_subscriber',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->assertNull($this->refactorFile('config.php', $configFileContent));
    }

    public function testSkipConfigWithoutServices(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE_WITH_REGISTERED_SERVICE);

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

        $this->assertNull($this->refactorFile('config.php', $configFileContent));
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

        $this->createFile('config.php', $configFileContent);

        $this->assertNull($this->refactorFile('services.php', self::SERVICES_FILE));
    }

    public function testSkipAlreadyRegisteredService(): void
    {
        $this->createFile('config.php', self::CONFIG_WITH_CLASS_ONLY_SERVICE);

        $servicesFileContent = <<<'CODE_SAMPLE'
<?php

return function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set('mautic.some.helper', SomeNamespace\SomeHelper::class);
};
CODE_SAMPLE;

        $this->assertNull($this->refactorFile('services.php', $servicesFileContent));
    }

    private function createFile(string $fileName, string $fileContent): string
    {
        $filePath = $this->temporaryDirectory.'/'.$fileName;
        file_put_contents($filePath, $fileContent);

        return $filePath;
    }

    /**
     * @return string|null printed file contents, null when the rule made no change
     */
    private function refactorFile(string $fileName, string $fileContent): ?string
    {
        $filePath = $this->createFile($fileName, $fileContent);

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
