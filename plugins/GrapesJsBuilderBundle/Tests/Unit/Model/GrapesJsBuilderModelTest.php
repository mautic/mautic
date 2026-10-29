<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class GrapesJsBuilderModelTest extends \PHPUnit\Framework\TestCase
{
    public function testAddOrEditEntityWithoutMatchingEntityAndNoRequestQuery(): void
    {
        $requestStack = new class() extends RequestStack {
            public function __construct()
            {
            }

            public function getCurrentRequest(): Request
            {
                return new Request();
            }
        };

        $emailRepository = new class() extends EmailRepository {
            public int $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            /**
             * @param object $entity
             * @param bool   $flush
             */
            public function saveEntity($entity, $flush = true): void
            {
                ++$this->saveEntityCallCount;
            }
        };

        $emailModel = $this->getEmailModel();

        $grapesJsBuilderRepository = new class() extends GrapesJsBuilderRepository {
            public int $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null): ?object
            {
                return null;
            }

            /**
             * @param object $entity
             * @param bool   $flush
             */
            public function saveEntity($entity, $flush = true): void
            {
                ++$this->saveEntityCallCount;
            }
        };

        /** @phpstan-ignore class.extendsFinalByPhpDoc */
        $entityManager = new class($grapesJsBuilderRepository) extends EntityManager {
            public function __construct(
                private readonly GrapesJsBuilderRepository $grapesJsBuilderRepository,
            ) {
            }

            public function getRepository($entityName)
            {
                Assert::assertSame(GrapesJsBuilder::class, $entityName);

                return $this->grapesJsBuilderRepository; // @phpstan-ignore-line
            }
        };

        $email = new Email();

        $grapeJsBuilderModel = new GrapesJsBuilderModel(
            $entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Router::class),
            $this->getTranslator(),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $grapeJsBuilderModel->autowireGrapesJsBuilderModel(
            $requestStack,
            $emailModel,
            $grapesJsBuilderRepository,
            $emailRepository,
        );

        $grapeJsBuilderModel->addOrEditEntity($email);

        // Not a GrapeJs email, so we are not saving anything.
        $this->assertSame(0, $grapesJsBuilderRepository->saveEntityCallCount);
        $this->assertSame(0, $emailRepository->saveEntityCallCount);
    }

    public function testAddOrEditEntityWithoutMatchingEntityAndGrapeRequestQuery(): void
    {
        $requestStack = new class() extends RequestStack {
            public function __construct()
            {
            }

            public function getCurrentRequest(): Request
            {
                return new Request(
                    [],
                    [
                        'grapesjsbuilder' => [
                            'customMjml' => '</mjml>',
                        ],
                        'emailform'       => [
                            'customHtml' => '</html>',
                        ],
                    ]
                );
            }
        };

        $emailRepository           = new class() extends EmailRepository {
            public int $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            /**
             * @param Email $entity
             */
            public function saveEntity($entity, $flush = true): void
            {
                ++$this->saveEntityCallCount;

                Assert::assertSame('</html>', $entity->getCustomHtml());
            }
        };

        $emailModel = $this->getEmailModel();

        $grapesJsBuilderRepository = new class() extends GrapesJsBuilderRepository {
            public int $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null): ?object
            {
                return null;
            }

            /**
             * @param GrapesJsBuilder $entity
             */
            public function saveEntity($entity, $flush = true): void
            {
                ++$this->saveEntityCallCount;

                Assert::assertSame('</mjml>', $entity->getCustomMjml());
            }
        };

        /** @phpstan-ignore class.extendsFinalByPhpDoc */
        $entityManager = new class($grapesJsBuilderRepository) extends EntityManager {
            public function __construct(
                private readonly GrapesJsBuilderRepository $grapesJsBuilderRepository,
            ) {
            }

            public function getRepository($entityName)
            {
                Assert::assertSame(GrapesJsBuilder::class, $entityName);

                return $this->grapesJsBuilderRepository; // @phpstan-ignore-line
            }
        };

        $email = new Email();

        $grapeJsBuilderModel = new GrapesJsBuilderModel(
            $entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Router::class),
            $this->getTranslator(),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $grapeJsBuilderModel->autowireGrapesJsBuilderModel(
            $requestStack,
            $emailModel,
            $grapesJsBuilderRepository,
            $emailRepository,
        );

        $grapeJsBuilderModel->addOrEditEntity($email);

        // Saving the entities now.
        $this->assertSame(1, $grapesJsBuilderRepository->saveEntityCallCount);
        $this->assertSame(1, $emailRepository->saveEntityCallCount);
    }

    public function testAddOrEditEntityGeneratesCustomHtmlFromMjmlWhenHtmlMissing(): void
    {
        $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Save</mj-text></mj-column></mj-section></mj-body></mjml>';

        $requestStack = new class($mjml) extends RequestStack {
            public function __construct(private readonly string $mjml)
            {
            }

            public function getCurrentRequest(): Request
            {
                return new Request(
                    [],
                    [
                        'grapesjsbuilder' => [
                            'customMjml' => $this->mjml,
                        ],
                        'emailform'       => [
                            'customHtml' => '',
                        ],
                    ]
                );
            }
        };

        $emailRepository = new class() extends EmailRepository {
            public int $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            /**
             * @param Email $entity
             */
            public function saveEntity($entity, $flush = true): void
            {
                ++$this->saveEntityCallCount;

                Assert::assertNotNull($entity->getCustomHtml());
                Assert::assertStringNotContainsString('<mjml>', (string) $entity->getCustomHtml());
                Assert::assertStringContainsString('Save', (string) $entity->getCustomHtml());
            }
        };

        $emailModel = $this->getEmailModel();

        $grapesJsBuilderRepository = new class($mjml) extends GrapesJsBuilderRepository {
            public int $saveEntityCallCount = 0;

            public function __construct(private readonly string $expectedMjml)
            {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null): ?object
            {
                return null;
            }

            /**
             * @param GrapesJsBuilder $entity
             */
            public function saveEntity($entity, $flush = true): void
            {
                ++$this->saveEntityCallCount;

                Assert::assertSame($this->expectedMjml, $entity->getCustomMjml());
            }
        };

        /** @phpstan-ignore class.extendsFinalByPhpDoc */
        $entityManager = new class($grapesJsBuilderRepository) extends EntityManager {
            public function __construct(
                private readonly GrapesJsBuilderRepository $grapesJsBuilderRepository,
            ) {
            }

            public function getRepository($entityName)
            {
                Assert::assertSame(GrapesJsBuilder::class, $entityName);

                return $this->grapesJsBuilderRepository; // @phpstan-ignore-line
            }
        };

        $email = new Email();

        $grapeJsBuilderModel = new GrapesJsBuilderModel(
            $entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Router::class),
            $this->getTranslator(),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $grapeJsBuilderModel->autowireGrapesJsBuilderModel(
            $requestStack,
            $emailModel,
            $grapesJsBuilderRepository,
            $emailRepository,
        );

        $grapeJsBuilderModel->addOrEditEntity($email);

        $this->assertSame(1, $grapesJsBuilderRepository->saveEntityCallCount);
        $this->assertSame(1, $emailRepository->saveEntityCallCount);
    }

    public function testAddOrEditEntityKeepsExistingCustomHtml(): void
    {
        $requestStack = new class() extends RequestStack {
            public function __construct()
            {
            }

            public function getCurrentRequest(): Request
            {
                return new Request(
                    [],
                    [
                        'grapesjsbuilder' => [
                            'customMjml' => '<mjml><mj-body><mj-text>Ignored</mj-text></mj-body></mjml>',
                        ],
                        'emailform'       => [
                            'customHtml' => '<html><body>Existing</body></html>',
                        ],
                    ]
                );
            }
        };

        $emailRepository = new class() extends EmailRepository {
            public function __construct()
            {
            }

            /**
             * @param Email $entity
             */
            public function saveEntity($entity, $flush = true): void
            {
                Assert::assertSame('<html><body>Existing</body></html>', $entity->getCustomHtml());
            }
        };

        $grapesJsBuilderRepository = new class() extends GrapesJsBuilderRepository {
            public function __construct()
            {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null): ?object
            {
                return null;
            }

            public function saveEntity($entity, $flush = true): void
            {
            }
        };

        /** @phpstan-ignore class.extendsFinalByPhpDoc */
        $entityManager = new class($grapesJsBuilderRepository) extends EntityManager {
            public function __construct(
                private readonly GrapesJsBuilderRepository $grapesJsBuilderRepository,
            ) {
            }

            public function getRepository($entityName)
            {
                return $this->grapesJsBuilderRepository; // @phpstan-ignore-line
            }
        };

        $grapeJsBuilderModel = new GrapesJsBuilderModel(
            $entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Router::class),
            $this->getTranslator(),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $grapeJsBuilderModel->autowireGrapesJsBuilderModel(
            $requestStack,
            $this->getEmailModel(),
            $grapesJsBuilderRepository,
            $emailRepository,
        );

        $grapeJsBuilderModel->addOrEditEntity(new Email());
    }

    private function getEmailModel(): EmailModel
    {
        return new class() extends EmailModel {
            public function __construct()
            {
            }
        };
    }

    private function getTranslator(): Translator
    {
        return new class() extends Translator {
            public function __construct()
            {
            }
        };
    }
}
