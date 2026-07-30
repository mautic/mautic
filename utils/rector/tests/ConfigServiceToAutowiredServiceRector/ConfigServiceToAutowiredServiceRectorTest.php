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
    /**
     * @var string
     */
    private const AUTOWIRABLE_HELPER_CLASS = 'Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper';

    private const CONFIG_WITH_PARAMETER_AWARE_SERVICE = <<<'CODE_SAMPLE'
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

    private const CONFIG_WITH_CLASS_ONLY_SERVICE = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.helper' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
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

    $services->set('mautic.some.helper', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class);

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
    $services->set('mautic.some.helper', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class);
    $services->alias(Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class, 'mautic.some.helper');

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
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
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
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
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
    $services->set('mautic.some.helper', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class);
    $services->alias(Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class, 'mautic.some.helper');
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
            "\$services->set('mautic.some.autowirable_helper', ".self::AUTOWIRABLE_HELPER_CLASS.'::class);',
            $this->readServicesFile()
        );
    }

    public function testMovesServiceWithServiceConstructorArgument(): void
    {
        // autowiring has no type to wire the parameter by, so the argument has to be kept
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.service_aware_helper' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ServiceAwareHelper::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile($configFileContent);
        $this->assertIsString($printedFileContent);
        $this->assertStringNotContainsString('mautic.some.service_aware_helper', $printedFileContent);

        $servicesFileContent = $this->readServicesFile();

        $this->assertStringContainsString(
            "->arg('\$someService', service('monolog.logger.mautic'));",
            $servicesFileContent
        );

        $this->assertStringContainsString(
            'use function Symfony\Component\DependencyInjection\Loader\Configurator\service;',
            $servicesFileContent
        );
    }

    public function testMovesServiceWithScalarConstructorArgument(): void
    {
        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile(self::CONFIG_WITH_PARAMETER_AWARE_SERVICE);
        $this->assertIsString($printedFileContent);
        $this->assertStringNotContainsString('mautic.some.parameter_aware_helper', $printedFileContent);

        $servicesFileContent = $this->readServicesFile();

        // the autowirable "translator" is dropped, the parameter is not
        $expectedSetStmt = <<<'CODE_SAMPLE'
    $services->set('mautic.some.parameter_aware_helper', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ParameterAwareHelper::class)
        ->arg('$secret', param('mautic.some_secret'));
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmt, $servicesFileContent);

        $this->assertStringContainsString(
            'use function Symfony\Component\DependencyInjection\Loader\Configurator\param;',
            $servicesFileContent
        );
    }

    public function testMovesServiceWithClassConstantTag(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'fixtures' => [
            'mautic.some.fixture' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'tag'   => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $this->assertStringContainsString(
            "\$services->set('mautic.some.fixture', ".self::AUTOWIRABLE_HELPER_CLASS.'::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);',
            $this->readServicesFile()
        );
    }

    public function testMovesServiceWithGroupDefaultTag(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'permissions' => [
            'mautic.some.permissions' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        // ServicePass tags the whole "permissions" group, the moved service has to keep that tag
        $this->assertStringContainsString(
            "\$services->set('mautic.some.permissions', ".self::AUTOWIRABLE_HELPER_CLASS."::class)->tag('mautic.permissions');",
            $this->readServicesFile()
        );
    }

    public function testKeepsFunctionImportsSorted(): void
    {
        $servicesFileContent = <<<'CODE_SAMPLE'
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->load('SomeNamespace\\', '../');
};
CODE_SAMPLE;

        $this->createFile('services.php', $servicesFileContent);

        $this->assertIsString($this->refactorConfigFile(self::CONFIG_WITH_PARAMETER_AWARE_SERVICE));

        $expectedImports = <<<'CODE_SAMPLE'
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
CODE_SAMPLE;

        $this->assertStringContainsString($expectedImports, $this->readServicesFile());
    }

    public function testSkipServiceWithExpressionConstructorArgument(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.service_aware_helper' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ServiceAwareHelper::class,
                'arguments' => [
                    '@=service("mautic.some.factory").getLogger()',
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

    public function testSkipServiceWithoutArgumentToWireBy(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'mautic.some.parameter_aware_helper' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ParameterAwareHelper::class,
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
            "\$services->set('mautic.some.voter', ".self::AUTOWIRABLE_HELPER_CLASS."::class)->tag('security.voter');",
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

    public function testMovesServiceWithAliasAsTagArgument(): void
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

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        // ServicePass hands the "alias" over as a tag argument, not as a service alias
        $this->assertStringContainsString(
            "\$services->set('mautic.some.translation_loader', ".self::AUTOWIRABLE_HELPER_CLASS."::class)->tag('translation.loader', ['alias' => 'mautic']);",
            $this->readServicesFile()
        );
    }

    public function testMovesHelperWithAliasOnGroupDefaultTag(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'helpers' => [
            'mautic.helper.twig.some' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'alias' => 'some_helper',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        // the "helpers" group tag is the one carrying the alias
        $this->assertStringContainsString(
            "\$services->set('mautic.helper.twig.some', ".self::AUTOWIRABLE_HELPER_CLASS."::class)->tag('twig.helper', ['alias' => 'some_helper']);",
            $this->readServicesFile()
        );
    }

    public function testSkipServiceWithAliasWithoutTagToCarryIt(): void
    {
        // "events" gets its tag from autoconfigure(), so there is no tag here the alias could ride on
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'events' => [
            'mautic.some.subscriber' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'alias' => 'some_subscriber',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertNull($this->refactorConfigFile($configFileContent));
        $this->assertSame(self::SERVICES_FILE, $this->readServicesFile());
    }

    public function testMovesServiceWithMethodCalls(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.subscriber' => [
                'class'       => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setSecret'     => ['%mautic.some_secret%'],
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $servicesFileContent = $this->readServicesFile();

        $expectedSetStmt = <<<'CODE_SAMPLE'
    $services->set('mautic.some.subscriber', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setSecret', [param('mautic.some_secret')]);
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmt, $servicesFileContent);

        $this->assertStringContainsString(
            'use function Symfony\Component\DependencyInjection\Loader\Configurator\service;',
            $servicesFileContent
        );
    }

    public function testMovesServiceBuiltByServiceFactory(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.user.manager' => [
                'class'     => Doctrine\ORM\EntityManager::class,
                'arguments' => Mautic\UserBundle\Entity\User::class,
                'factory'   => ['@doctrine', 'getManagerForClass'],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile($configFileContent);
        $this->assertIsString($printedFileContent);
        $this->assertStringNotContainsString('mautic.user.manager', $printedFileContent);

        $servicesFileContent = $this->readServicesFile();

        // the arguments feed the factory call, so they keep their positions instead of being autowired away
        $expectedSetStmts = <<<'CODE_SAMPLE'
    $services->set('mautic.user.manager', Doctrine\ORM\EntityManager::class)
        ->factory([service('doctrine'), 'getManagerForClass'])
        ->args([Mautic\UserBundle\Entity\User::class]);
    $services->alias(Doctrine\ORM\EntityManager::class, 'mautic.user.manager');
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmts, $servicesFileContent);

        $this->assertStringContainsString(
            'use function Symfony\Component\DependencyInjection\Loader\Configurator\service;',
            $servicesFileContent
        );
    }

    public function testMovesServiceBuiltByClassFactory(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.http_client' => [
                'class'   => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'factory' => [Symfony\Component\HttpClient\HttpClient::class, 'create'],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $expectedSetStmt = <<<'CODE_SAMPLE'
    $services->set('mautic.some.http_client', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class)
        ->factory([Symfony\Component\HttpClient\HttpClient::class, 'create']);
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmt, $this->readServicesFile());
    }

    public function testMovesServiceBuiltByFactoryNamedInSingleString(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.entity_descriptor' => [
                'class'     => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'factory'   => 'SomeNamespace\SomeDescriptorFactory::build',
                'arguments' => [
                    '%mautic.some_secret%',
                    'router',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $servicesFileContent = $this->readServicesFile();

        // the "Class::method" string names the very same pair as ['Class', 'method']
        $expectedSetStmt = <<<'CODE_SAMPLE'
    $services->set('mautic.some.entity_descriptor', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class)
        ->factory([SomeNamespace\SomeDescriptorFactory::class, 'build'])
        ->args([param('mautic.some_secret'), service('router')]);
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmt, $servicesFileContent);

        $expectedImports = <<<'CODE_SAMPLE'
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
CODE_SAMPLE;

        $this->assertStringContainsString($expectedImports, $servicesFileContent);
    }

    public function testSkipServiceBuiltByFactoryFunction(): void
    {
        // a plain function name is no ['@service', 'method'] pair, there is no telling what it builds
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.helper' => [
                'class'   => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'factory' => 'some_factory_function',
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

    public function testMovesThirdPartyClassOverride(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'others' => [
            'oneup_uploader.controller.dropzone.class' => [
                'class' => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $expectedSetStmts = <<<'CODE_SAMPLE'
    $services->set('oneup_uploader.controller.dropzone.class', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class);
    $services->alias(Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class, 'oneup_uploader.controller.dropzone.class');
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmts, $this->readServicesFile());
    }

    public function testMovesServiceWithServiceAliases(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.sms.twilio.transport' => [
                'class'        => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ServiceAwareHelper::class,
                'arguments'    => [
                    'mautic.sms.twilio.configuration',
                ],
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
                    'integrationAlias' => 'Twilio',
                ],
                'serviceAliases' => [
                    'sms_api',
                    'mautic.sms.api',
                ],
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $printedFileContent = $this->refactorConfigFile($configFileContent);
        $this->assertIsString($printedFileContent);
        $this->assertStringNotContainsString('mautic.sms.twilio.transport', $printedFileContent);

        // every "serviceAliases" entry is a service alias of its very own, see ServicePass
        $expectedSetStmts = <<<'CODE_SAMPLE'
    $services->set('mautic.sms.twilio.transport', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ServiceAwareHelper::class)
        ->arg('$someService', service('mautic.sms.twilio.configuration'))
        ->tag('mautic.sms_transport', ['integrationAlias' => 'Twilio']);
    $services->alias(Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\ServiceAwareHelper::class, 'mautic.sms.twilio.transport');
    $services->alias('sms_api', 'mautic.sms.twilio.transport');
    $services->alias('mautic.sms.api', 'mautic.sms.twilio.transport');
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmts, $this->readServicesFile());
    }

    public function testMovesServiceWithSingleServiceAlias(): void
    {
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'helpers' => [
            'mautic.helper.core_parameters' => [
                'class'        => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'serviceAlias' => 'mautic.config',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $expectedSetStmts = <<<'CODE_SAMPLE'
    $services->set('mautic.helper.core_parameters', Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class)->tag('twig.helper');
    $services->alias(Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class, 'mautic.helper.core_parameters');
    $services->alias('mautic.config', 'mautic.helper.core_parameters');
CODE_SAMPLE;

        $this->assertStringContainsString($expectedSetStmts, $this->readServicesFile());
    }

    public function testMovesServiceWithServiceAliasPattern(): void
    {
        // the alias name is an sprintf() pattern, the service name filling its placeholder
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.helper' => [
                'class'        => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'serviceAlias' => '%%s.api',
            ],
        ],
    ],
];
CODE_SAMPLE;

        $this->createFile('services.php', self::SERVICES_FILE);

        $this->assertIsString($this->refactorConfigFile($configFileContent));

        $this->assertStringContainsString(
            "\$services->alias('mautic.some.helper.api', 'mautic.some.helper');",
            $this->readServicesFile()
        );
    }

    public function testSkipServiceWithParameterLikeServiceAlias(): void
    {
        // "%mautic.some_alias%" is no sprintf() placeholder, there is no telling what the alias would be
        $configFileContent = <<<'CODE_SAMPLE'
<?php

return [
    'services' => [
        'other' => [
            'mautic.some.helper' => [
                'class'        => Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source\AutowirableHelper::class,
                'serviceAlias' => '%mautic.some_alias%',
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
