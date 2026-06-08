<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Entity\Page;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractCommonModel<GrapesJsBuilder>
 */
class GrapesJsBuilderModel extends AbstractCommonModel
{
    public function __construct(
        private RequestStack $requestStack,
        private EmailModel $emailModel,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return GrapesJsBuilderRepository
     */
    public function getRepository()
    {
        /** @var GrapesJsBuilderRepository $repository */
        $repository = $this->em->getRepository(GrapesJsBuilder::class);

        $repository->setTranslator($this->translator);

        return $repository;
    }

    /**
     * Add or edit email settings based on request.
     */
    public function addOrEditEntity(Email $email): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest || !$currentRequest->request->has('grapesjsbuilder')) {
            return;
        }

        $data = $currentRequest->request->all('grapesjsbuilder');
        $this->handleEmailEntity($email, $data, $currentRequest);
    }

    public function addOrEditPageEntity(Page $page): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest || !$currentRequest->request->has('grapesjsbuilder')) {
            return;
        }

        $data = $currentRequest->request->all('grapesjsbuilder');
        $this->handlePageEntity($page, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleEmailEntity(Email $entity, array $data, Request $request): void
    {
        if ($this->emailModel->isUpdatingTranslationChildren()) {
            return;
        }

        $grapesJsBuilder = $this->getRepository()->findOneBy(['email' => $entity]);

        if (!$grapesJsBuilder) {
            $grapesJsBuilder = new GrapesJsBuilder();
            $grapesJsBuilder->setEmail($entity);
        }

        if (array_key_exists('customMjml', $data)) {
            $grapesJsBuilder->setCustomMjml($data['customMjml']);
        }

        $this->updateEntityEditorState($entity, $data);
        $this->getRepository()->saveEntity($grapesJsBuilder);

        $emailForm  = $request->request->all('emailform');
        $customHtml = is_array($emailForm) ? ($emailForm['customHtml'] ?? null) : null;
        if (null === $customHtml) {
            $customHtml = $request->request->get('customHtml') ?? null;
        }

        $entity->setCustomHtml($customHtml);
        $this->emailModel->getRepository()->saveEntity($entity);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handlePageEntity(Page $entity, array $data): void
    {
        if (!$this->updateEntityEditorState($entity, $data)) {
            return;
        }

        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hasEditorStatePayload(array $data): bool
    {
        return array_key_exists('editorState', $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeEditorState(mixed $editorState): ?array
    {
        if (is_array($editorState)) {
            return $editorState;
        }

        if (!is_string($editorState) || '' === trim($editorState)) {
            return null;
        }

        $decoded = json_decode($editorState, true);
        if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeContent(mixed $content): array
    {
        $normalizedContent = [];

        if (is_array($content)) {
            $normalizedContent = $content;
        } elseif (is_string($content)) {
            $decodedContent = json_decode($content, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($decodedContent)) {
                $normalizedContent = $decodedContent;
            }
        }

        return $normalizedContent;
    }

    /**
     * @param array<string, mixed>      $content
     * @param array<string, mixed>|null $editorState
     *
     * @return array<string, mixed>
     */
    private function mergeEditorStateIntoContent(array $content, ?array $editorState): array
    {
        if (!isset($content['grapesjsbuilder']) || !is_array($content['grapesjsbuilder'])) {
            $content['grapesjsbuilder'] = [];
        }

        $content['grapesjsbuilder']['editorState'] = $editorState;
        $content['grapesjsbuilder']['updatedAt']   = (new \DateTime())->format('c');

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateEntityEditorState(Email|Page $entity, array $data): bool
    {
        if (!$this->hasEditorStatePayload($data)) {
            return false;
        }

        $rawEditorState = $data['editorState'] ?? null;
        $editorState    = $this->decodeEditorState($rawEditorState);
        $content        = $this->normalizeContent($entity->getContent());
        $entity->setContent($this->mergeEditorStateIntoContent($content, $editorState));

        return true;
    }

    public function getGrapesJsFromEmailId(?int $emailId)
    {
        if ($email = $this->emailModel->getEntity($emailId)) {
            return $this->getRepository()->findOneBy(['email' => $email]);
        }
    }
}
