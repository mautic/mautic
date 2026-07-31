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
use Mautic\PageBundle\Entity\Page;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class GrapesJsBuilderModelEditorStateTest extends TestCase
{
    public function testAddOrEditEntityStoresDecodedEditorStateAndCustomHtmlFallback(): void
    {
        $requestStack = new RequestStack([new Request([], [
            'grapesjsbuilder' => [
                'customMjml'  => '<mjml/>',
                'editorState' => '{"pages":[{"id":"main"}]}',
            ],
            'customHtml' => '<html/>',
        ])]);

        /** @var MockObject&EmailRepository $emailRepository */
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects($this->once())
            ->method('saveEntity')
            ->with(self::isInstanceOf(Email::class));

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->method('isUpdatingTranslationChildren')->willReturn(false);

        /** @var MockObject&GrapesJsBuilderRepository $grapesRepository */
        $grapesRepository = $this->createMock(GrapesJsBuilderRepository::class);
        $grapesRepository->method('findOneBy')->willReturn(null);
        $grapesRepository->expects($this->once())
            ->method('saveEntity')
            ->with(self::callback(static fn ($entity): bool => $entity instanceof GrapesJsBuilder && '<mjml/>' === $entity->getCustomMjml()));

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createStub(EntityManager::class);

        $model = $this->getModel($requestStack, $emailModel, $entityManager, $grapesRepository, $emailRepository);

        $email = new Email();
        $email->setContent(['existing' => true]);

        $model->addOrEditEntity($email);

        $this->assertSame('<html/>', $email->getCustomHtml());
        $content = $email->getContent();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('grapesjsbuilder', $content);
        $this->assertIsArray($content['grapesjsbuilder']);
        $this->assertSame(['pages' => [['id' => 'main']]], $content['grapesjsbuilder']['editorState']);
        $this->assertArrayHasKey('updatedAt', $content['grapesjsbuilder']);
    }

    public function testAddOrEditEntitySkipsWhenTranslationChildrenAreUpdating(): void
    {
        $requestStack = new RequestStack([new Request([], [
            'grapesjsbuilder' => [
                'customMjml'  => '<mjml/>',
                'editorState' => '{"pages":[]}',
            ],
        ])]);

        /** @var MockObject&EmailRepository $emailRepository */
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects($this->never())->method('saveEntity');

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->method('isUpdatingTranslationChildren')->willReturn(true);

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->never())->method('getRepository');

        $model = $this->getModel($requestStack, $emailModel, $entityManager, null, $emailRepository);

        $model->addOrEditEntity(new Email());
    }

    public function testAddOrEditPageEntityPersistsOnlyWhenEditorStateProvided(): void
    {
        $requestStack = new RequestStack([new Request([], [
            'grapesjsbuilder' => [
                'editorState' => ['pages' => [['id' => 'landing']]],
            ],
        ])]);

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createStub(EmailModel::class);

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $model = $this->getModel($requestStack, $emailModel, $entityManager);

        $page = new Page();
        $page->setContent(['existing' => 'value']);

        $model->addOrEditPageEntity($page);

        $content = $page->getContent();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('grapesjsbuilder', $content);
        $this->assertIsArray($content['grapesjsbuilder']);
        $this->assertSame(['pages' => [['id' => 'landing']]], $content['grapesjsbuilder']['editorState']);

        $requestStackNoEditor = new RequestStack([new Request([], [
            'grapesjsbuilder' => [
                'customMjml' => '<mjml/>',
            ],
        ])]);

        /** @var MockObject&EntityManager $entityManagerNoEditor */
        $entityManagerNoEditor = $this->createMock(EntityManager::class);
        $entityManagerNoEditor->expects($this->never())->method('persist');
        $entityManagerNoEditor->expects($this->never())->method('flush');

        $modelNoEditor = $this->getModel($requestStackNoEditor, $emailModel, $entityManagerNoEditor);
        $modelNoEditor->addOrEditPageEntity(new Page());
    }

    private function getModel(
        RequestStack $requestStack,
        EmailModel $emailModel,
        EntityManager $entityManager,
        ?GrapesJsBuilderRepository $grapesJsBuilderRepository = null,
        ?EmailRepository $emailRepository = null,
    ): GrapesJsBuilderModel {
        return new GrapesJsBuilderModel(
            $requestStack,
            $emailModel,
            $entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Router::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $grapesJsBuilderRepository ?? $this->createStub(GrapesJsBuilderRepository::class), // $grapesJsBuilderRepository
            $emailRepository ?? $this->createStub(EmailRepository::class), // $emailRepository
        );
    }
}
