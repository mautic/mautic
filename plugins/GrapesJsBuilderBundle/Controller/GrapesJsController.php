<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class GrapesJsController extends CommonController
{
    public const OBJECT_TYPE = ['email', 'page'];

    private function isAuthorizedObjectType(string $objectType): bool
    {
        return in_array($objectType, self::OBJECT_TYPE, true);
    }

    private function getAclPrefix(string $objectType): string
    {
        return 'page' === $objectType ? 'page:pages:' : 'email:emails:';
    }

    /**
     * @return array<mixed, mixed>
     */
    private function normalizeContentToArray(mixed $content): array
    {
        if (is_array($content)) {
            return $content;
        }

        if (!is_string($content) || '' === $content) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\JsonException) {
        }

        if (!str_starts_with($content, 'a:')) {
            return [];
        }

        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $unserialized = unserialize($content, ['allowed_classes' => false]);
        } catch (\Throwable) {
            return [];
        } finally {
            restore_error_handler();
        }

        return is_array($unserialized) ? $unserialized : [];
    }

    /**
     * @param array<string, mixed> $content
     *
     * @return array<string, mixed>|null
     */
    private function extractEditorStateFromContent(array $content): ?array
    {
        $builderConfig = $content['grapesjsbuilder'] ?? null;

        if (!is_array($builderConfig)) {
            return null;
        }

        $editorState = $builderConfig['editorState'] ?? null;

        if (is_array($editorState)) {
            return $editorState;
        }

        if (!is_string($editorState) || '' === trim($editorState)) {
            return null;
        }

        try {
            $decodedEditorState = json_decode($editorState, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decodedEditorState) ? $decodedEditorState : null;
    }

    public function builderAction(
        Request $request,
        LoggerInterface $mauticLogger,
        ThemeHelper $themeHelper,
        string $objectType,
        string $objectId,
    ): Response {
        if (!$this->isAuthorizedObjectType($objectType)) {
            throw new ConflictHttpException('Object not authorized to load custom builder');
        }

        /** @var \Mautic\EmailBundle\Model\EmailModel|\Mautic\PageBundle\Model\PageModel $model */
        $model      = $this->getModel($objectType);
        $aclToCheck = $this->getAclPrefix($objectType);

        // permission check
        if (str_contains((string) $objectId, 'new')) {
            $isNew = true;

            if (!$this->security->isGranted($aclToCheck.'create')) {
                return $this->accessDenied();
            }

            /** @var Email|Page $entity */
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            /** @var Email|Page $entity */
            $entity = $model->getEntity((int) $objectId);
            $isNew  = false;

            if (null == $entity
                || !$this->security->hasEntityAccess(
                    $aclToCheck.'viewown',
                    $aclToCheck.'viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }
        }

        $type             = 'html';
        $template         = InputHelper::clean($request->query->get('template'));
        $resetEditorState = $request->query->getBoolean('resetEditorState', false);
        if (!$template) {
            $mauticLogger->warning('Grapesjs: no template in query');

            return $this->json(false);
        }
        $templateName = '@themes/'.$template.'/html/'.$objectType;
        $content      = $resetEditorState ? [] : $entity->getContent();

        // Check for MJML template
        // @deprecated - use mjml directly in email.html.twig
        if ($logicalName = $this->checkForMjmlTemplate($templateName.'.mjml.twig')) {
            $type        = 'mjml';
        } else {
            $logicalName = $themeHelper->checkForTwigTemplate($templateName.'.html.twig');
        }

        // Replace short codes to emoji
        $content = array_map(fn ($text) => EmojiHelper::toEmoji($text, 'short'), $content);

        $renderedTemplate =  $this->renderView(
            $logicalName,
            [
                'isNew'     => $isNew,
                'content'   => $content,
                $objectType => $entity,
                'template'  => $template,
                'basePath'  => $request->getBasePath(),
            ]
        );

        if (str_contains($renderedTemplate, '<mjml>')) {
            $type = 'mjml';
        }

        $renderedTemplateHtml = ('html' === $type) ? $renderedTemplate : '';
        $renderedTemplateMjml = ('mjml' === $type) ? $renderedTemplate : '';

        return $this->render(
            '@GrapesJsBuilder/Builder/template.html.twig',
            [
                'templateHtml' => $renderedTemplateHtml,
                'templateMjml' => $renderedTemplateMjml,
            ]
        );
    }

    public function editorStateAction(
        string $objectType,
        string $objectId,
    ): Response {
        if (!$this->isAuthorizedObjectType($objectType)) {
            throw new ConflictHttpException('Object not authorized to load custom builder');
        }

        if (str_contains($objectId, 'new')) {
            return $this->json(['editorState' => null]);
        }

        $model      = $this->getModel($objectType);
        $aclToCheck = $this->getAclPrefix($objectType);

        /** @var Email|Page|null $entity */
        $entity = $model->getEntity((int) $objectId);

        if (null === $entity
            || !$this->security->hasEntityAccess(
                $aclToCheck.'viewown',
                $aclToCheck.'viewother',
                $entity->getCreatedBy()
            )
        ) {
            return $this->accessDenied();
        }

        $content     = $this->normalizeContentToArray($entity->getContent());
        $editorState = $this->extractEditorStateFromContent($content);

        return $this->json(['editorState' => $editorState]);
    }

    /**
     * @deprecated deprecated since version 5.0 - use mjml directly in email.html.twig
     */
    private function checkForMjmlTemplate($template)
    {
        $twig = $this->container->get('twig');

        if ($twig->getLoader()->exists($template)) {
            return $template;
        }

        return null;
    }
}
