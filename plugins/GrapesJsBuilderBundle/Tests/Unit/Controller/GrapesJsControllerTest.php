<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\Controller;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\GrapesJsBuilderBundle\Controller\GrapesJsController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class GrapesJsControllerTest extends TestCase
{
    public function testEditorStateActionThrowsForUnsupportedObjectType(): void
    {
        $controller = $this->getControllerForEditorState($this->createMock(CorePermissions::class), null);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Object not authorized to load custom builder');

        $controller->editorStateAction('asset', '1');
    }

    public function testEditorStateActionReturnsNullForNewEntity(): void
    {
        $controller = $this->getControllerForEditorState($this->createMock(CorePermissions::class), null);
        $response   = $controller->editorStateAction('email', 'new123');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame('{"editorState":null,"templateHead":null}', $response->getContent());
    }

    public function testEditorStateActionReturnsEditorStateFromJsonContent(): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $entity = $this->createMock(Email::class);
        $entity->method('getCreatedBy')->willReturn(1);
        $entity->method('getContent')->willReturn(json_encode([
            'grapesjsbuilder' => [
                'editorState'  => json_encode(['components' => [['type' => 'text']]]),
                'templateHead' => '<link rel="stylesheet" href="https://example.test/theme.css">',
            ],
        ]));

        $controller = $this->getControllerForEditorState($security, $entity);
        $response   = $controller->editorStateAction('email', '15');

        self::assertSame(
            [
                'editorState'  => ['components' => [['type' => 'text']]],
                'templateHead' => '<link rel="stylesheet" href="https://example.test/theme.css">',
            ],
            json_decode((string) $response->getContent(), true)
        );
    }

    public function testEditorStateActionReturnsEditorStateFromSerializedContent(): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $entity = $this->createMock(Email::class);
        $entity->method('getCreatedBy')->willReturn(1);
        $entity->method('getContent')->willReturn('a:1:{s:15:"grapesjsbuilder";a:2:{s:11:"editorState";a:1:{s:5:"pages";a:0:{}}s:12:"templateHead";s:35:"<style>.page{display:block}</style>";}}');

        $controller = $this->getControllerForEditorState($security, $entity);
        $response   = $controller->editorStateAction('email', '33');

        self::assertSame(
            [
                'editorState'  => ['pages' => []],
                'templateHead' => '<style>.page{display:block}</style>',
            ],
            json_decode((string) $response->getContent(), true)
        );
    }

    public function testEditorStateActionReturnsNullWhenEditorStateCannotBeDecoded(): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $entity = $this->createMock(Email::class);
        $entity->method('getCreatedBy')->willReturn(1);
        $entity->method('getContent')->willReturn([
            'grapesjsbuilder' => [
                'editorState' => 'not-a-json',
            ],
        ]);

        $controller = $this->getControllerForEditorState($security, $entity);
        $response   = $controller->editorStateAction('email', '20');

        self::assertSame('{"editorState":null,"templateHead":null}', $response->getContent());
    }

    private function getControllerForEditorState(CorePermissions $security, ?Email $entity): GrapesJsController
    {
        return new class($security, $entity) extends GrapesJsController {
            public function __construct(
                private CorePermissions $testSecurity,
                private ?Email $testEntity,
            ) {
                $this->security = $this->testSecurity;
                $this->setContainer(new Container());
            }

            /**
             * @return AbstractCommonModel<object>
             */
            protected function getModel($modelNameKey): MauticModelInterface
            {
                return new class($this->testEntity) extends AbstractCommonModel {
                    public function __construct(
                        private ?Email $entity,
                    ) {
                    }

                    public function getEntity($id = null): ?object
                    {
                        return $this->entity;
                    }
                };
            }
        };
    }
}
