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
use PHPUnit\Framework\Assert;
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
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [
            'grapesjsbuilder' => [
                'customMjml'   => '<mjml/>',
                'editorState'  => '{"pages":[{"id":"main"}]}',
                'templateHead' => '<link rel="stylesheet" href="https://example.test/theme.css">',
            ],
            'customHtml' => '<html/>',
        ]));

        /** @var MockObject&EmailRepository $emailRepository */
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects(self::once())
            ->method('saveEntity')
            ->with(self::isInstanceOf(Email::class));

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->method('isUpdatingTranslationChildren')->willReturn(false);
        $emailModel->method('getRepository')->willReturn($emailRepository);

        /** @var MockObject&GrapesJsBuilderRepository $grapesRepository */
        $grapesRepository = $this->createMock(GrapesJsBuilderRepository::class);
        $grapesRepository->method('findOneBy')->willReturn(null);
        $grapesRepository->expects(self::once())
            ->method('saveEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity instanceof GrapesJsBuilder && '<mjml/>' === $entity->getCustomMjml();
            }));

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->with(GrapesJsBuilder::class)->willReturn($grapesRepository);

        $model = $this->getModel($requestStack, $emailModel, $entityManager);

        $email = new Email();
        $email->setContent(['existing' => true]);

        $model->addOrEditEntity($email);

        Assert::assertSame('<html/>', $email->getCustomHtml());
        $content = $email->getContent();
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('grapesjsbuilder', $content);
        Assert::assertIsArray($content['grapesjsbuilder']);
        Assert::assertSame(['pages' => [['id' => 'main']]], $content['grapesjsbuilder']['editorState']);
        Assert::assertSame('<link rel="stylesheet" href="https://example.test/theme.css">', $content['grapesjsbuilder']['templateHead']);
        Assert::assertArrayHasKey('updatedAt', $content['grapesjsbuilder']);
    }

    public function testAddOrEditEntitySkipsWhenTranslationChildrenAreUpdating(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [
            'grapesjsbuilder' => [
                'customMjml'  => '<mjml/>',
                'editorState' => '{"pages":[]}',
            ],
        ]));

        /** @var MockObject&EmailRepository $emailRepository */
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects(self::never())->method('saveEntity');

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->method('isUpdatingTranslationChildren')->willReturn(true);
        $emailModel->method('getRepository')->willReturn($emailRepository);

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::never())->method('getRepository');

        $model = $this->getModel($requestStack, $emailModel, $entityManager);

        $model->addOrEditEntity(new Email());
    }

    public function testAddOrEditEntitySanitizesRuntimeAssetsFromTemplateHead(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [
            'grapesjsbuilder' => [
                'editorState'  => '{"pages":[{"id":"main"}]}',
                'templateHead' => '<meta charset="utf-8">'
                    .'<link rel="stylesheet" href="/themes/theme-a/css/theme.css">'
                    .'<link rel="stylesheet" href="/plugins/GrapesJsBuilderBundle/Assets/library/js/dist/builder.css?v=1">'
                    .'<script src="/media/libraries/ckeditor/ckeditor.js"></script>'
                    .'<style data-cke="true">.ck-editor{position:fixed;}</style>'
                    .'<style>.ck-content{line-height:1.6;}</style>',
            ],
        ]));

        /** @var MockObject&EmailRepository $emailRepository */
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects(self::once())
            ->method('saveEntity')
            ->with(self::isInstanceOf(Email::class));

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->method('isUpdatingTranslationChildren')->willReturn(false);
        $emailModel->method('getRepository')->willReturn($emailRepository);

        /** @var MockObject&GrapesJsBuilderRepository $grapesRepository */
        $grapesRepository = $this->createMock(GrapesJsBuilderRepository::class);
        $grapesRepository->method('findOneBy')->willReturn(null);
        $grapesRepository->expects(self::once())
            ->method('saveEntity')
            ->with(self::isInstanceOf(GrapesJsBuilder::class));

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->with(GrapesJsBuilder::class)->willReturn($grapesRepository);

        $model = $this->getModel($requestStack, $emailModel, $entityManager);

        $email = new Email();
        $model->addOrEditEntity($email);

        $content = $email->getContent();

        Assert::assertIsArray($content);
        Assert::assertIsArray($content['grapesjsbuilder']);
        Assert::assertSame(
            '<meta charset="utf-8"><link rel="stylesheet" href="/themes/theme-a/css/theme.css">',
            $content['grapesjsbuilder']['templateHead']
        );
    }

    public function testAddOrEditPageEntityPersistsOnlyWhenEditorStateProvided(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [
            'grapesjsbuilder' => [
                'editorState' => ['pages' => [['id' => 'landing']]],
            ],
        ]));

        /** @var MockObject&EmailModel $emailModel */
        $emailModel = $this->createMock(EmailModel::class);

        /** @var MockObject&EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $model = $this->getModel($requestStack, $emailModel, $entityManager);

        $page = new Page();
        $page->setContent(['existing' => 'value']);

        $model->addOrEditPageEntity($page);

        $content = $page->getContent();
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey('grapesjsbuilder', $content);
        Assert::assertIsArray($content['grapesjsbuilder']);
        Assert::assertSame(['pages' => [['id' => 'landing']]], $content['grapesjsbuilder']['editorState']);

        $requestStackTemplateHead = new RequestStack();
        $requestStackTemplateHead->push(new Request([], [
            'grapesjsbuilder' => [
                'templateHead' => '<style>.landing{display:block}</style>',
            ],
        ]));

        /** @var MockObject&EntityManager $entityManagerTemplateHead */
        $entityManagerTemplateHead = $this->createMock(EntityManager::class);
        $entityManagerTemplateHead->expects(self::once())->method('persist');
        $entityManagerTemplateHead->expects(self::once())->method('flush');

        $modelTemplateHead = $this->getModel($requestStackTemplateHead, $emailModel, $entityManagerTemplateHead);

        $pageWithTemplateHead = new Page();
        $modelTemplateHead->addOrEditPageEntity($pageWithTemplateHead);

        $pageWithTemplateHeadContent = $pageWithTemplateHead->getContent();
        Assert::assertIsArray($pageWithTemplateHeadContent);
        Assert::assertSame('<style>.landing{display:block}</style>', $pageWithTemplateHeadContent['grapesjsbuilder']['templateHead']);
        Assert::assertNull($pageWithTemplateHeadContent['grapesjsbuilder']['editorState']);

        $requestStackNoEditor = new RequestStack();
        $requestStackNoEditor->push(new Request([], [
            'grapesjsbuilder' => [
                'customMjml' => '<mjml/>',
            ],
        ]));

        /** @var MockObject&EntityManager $entityManagerNoEditor */
        $entityManagerNoEditor = $this->createMock(EntityManager::class);
        $entityManagerNoEditor->expects(self::never())->method('persist');
        $entityManagerNoEditor->expects(self::never())->method('flush');

        $modelNoEditor = $this->getModel($requestStackNoEditor, $emailModel, $entityManagerNoEditor);
        $modelNoEditor->addOrEditPageEntity(new Page());
    }

    private function getModel(
        RequestStack $requestStack,
        EmailModel $emailModel,
        EntityManager $entityManager,
    ): GrapesJsBuilderModel {
        return new GrapesJsBuilderModel(
            $requestStack,
            $emailModel,
            $entityManager,
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Router::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );
    }
}
