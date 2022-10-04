<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GrapesJsBuilderModelTest extends \PHPUnit\Framework\TestCase
{
    public function testAddOrEditEntityWithoutMatchingEntityAndNoRequestQuery(): void
    {
        $requestStack = new class() extends RequestStack {
            public function __construct()
            {
            }

            public function getCurrentRequest()
            {
                return new Request();
            }
        };

        $emailRepository                = new class() extends EmailRepository {
            public $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            public function saveEntity($entity, $flush = true)
            {
                ++$this->saveEntityCallCount;
            }
        };

        $emailModel = $this->getEmailModel($emailRepository);

        $grapesJsBuilderRepository      = new class() extends GrapesJsBuilderRepository {
            public $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null)
            {
                return null;
            }

            public function saveEntity($entity, $flush = true)
            {
                ++$this->saveEntityCallCount;
            }
        };

        $entityManager = new class($grapesJsBuilderRepository) extends EntityManager {
            private $grapesJsBuilderRepository;

            public function __construct(GrapesJsBuilderRepository $grapesJsBuilderRepository)
            {
                $this->grapesJsBuilderRepository = $grapesJsBuilderRepository;
            }

            public function getRepository($entityName)
            {
                Assert::assertSame(GrapesJsBuilder::class, $entityName);

                return $this->grapesJsBuilderRepository;
            }
        };

        $email = new Email();

        $grapeJsBuilderModel = new GrapesJsBuilderModel($requestStack, $emailModel);
        $grapeJsBuilderModel->setEntityManager($entityManager);
        $grapeJsBuilderModel->setTranslator($this->getTranslator());

        $grapeJsBuilderModel->addOrEditEntity($email);

        // Not a GrapeJs email, so we are not saving anything.
        Assert::assertSame(0, $grapesJsBuilderRepository->saveEntityCallCount);
        Assert::assertSame(0, $emailRepository->saveEntityCallCount);
    }

    public function testAddOrEditEntityWithoutMatchingEntityAndGrapeRequestQuery(): void
    {
        $requestStack = new class() extends RequestStack {
            public function __construct()
            {
            }

            public function getCurrentRequest()
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

        $emailRepository                = new class() extends EmailRepository {
            public $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            /**
             * @param Email $entity
             */
            public function saveEntity($entity, $flush = true)
            {
                ++$this->saveEntityCallCount;

                Assert::assertSame('</html>', $entity->getCustomHtml());
            }
        };

        $emailModel = $this->getEmailModel($emailRepository);

        $grapesJsBuilderRepository      = new class() extends GrapesJsBuilderRepository {
            public $saveEntityCallCount = 0;

            public function __construct()
            {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null)
            {
                return null;
            }

            /**
             * @param GrapesJsBuilder $entity
             */
            public function saveEntity($entity, $flush = true)
            {
                ++$this->saveEntityCallCount;

                Assert::assertSame('</mjml>', $entity->getCustomMjml());
            }
        };

        $entityManager = new class($grapesJsBuilderRepository) extends EntityManager {
            private $grapesJsBuilderRepository;

            public function __construct(GrapesJsBuilderRepository $grapesJsBuilderRepository)
            {
                $this->grapesJsBuilderRepository = $grapesJsBuilderRepository;
            }

            public function getRepository($entityName)
            {
                Assert::assertSame(GrapesJsBuilder::class, $entityName);

                return $this->grapesJsBuilderRepository;
            }
        };

        $email = new Email();

        $grapeJsBuilderModel = new GrapesJsBuilderModel($requestStack, $emailModel);
        $grapeJsBuilderModel->setEntityManager($entityManager);
        $grapeJsBuilderModel->setTranslator($this->getTranslator());

        $grapeJsBuilderModel->addOrEditEntity($email);

        // Saving the entities now.
        Assert::assertSame(1, $grapesJsBuilderRepository->saveEntityCallCount);
        Assert::assertSame(1, $emailRepository->saveEntityCallCount);
    }

    private function getEmailModel(EmailRepository $emailRepository): EmailModel
    {
        return new class($emailRepository) extends EmailModel {
            private $emailRepository;

            public function __construct(EmailRepository $emailRepository)
            {
                $this->emailRepository = $emailRepository;
            }

            public function getRepository()
            {
                return $this->emailRepository;
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
