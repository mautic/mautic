<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageDraft;
use Mautic\PageBundle\Entity\PageDraftRepository;
use Mautic\PageBundle\Entity\PageRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageDraftModel
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var PageDraftRepository
     */
    private $pageDraftRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PageDraftRepository $pageDraftRepository,
        PageRepository $pageRepository
    ) {
        $this->entityManager       = $entityManager;
        $this->pageDraftRepository = $pageDraftRepository;
        $this->pageRepository      = $pageRepository;
    }

    /**
     * @throws \Exception
     */
    public function createDraft(Page $page, string $html, string $template, bool $publicPreview = true): PageDraft
    {
        $pageDraft = $this->pageDraftRepository->findOneBy(['page' => $page]);
        if (!is_null($pageDraft)) {
            throw new \Exception(sprintf('Draft already exists for page %d', $page->getId()));
        }
        $pageDraft = new PageDraft($page, $html, $template, $publicPreview);

        $this->entityManager->persist($pageDraft);
        $this->entityManager->flush();

        return $pageDraft;
    }

    public function saveDraft(PageDraft $pageDraft): void
    {
        $this->entityManager->persist($pageDraft);
        $this->entityManager->flush();
    }

    public function deleteDraft(Page $page): void
    {
        if (is_null($pageDraft = $page->getDraft())) {
            throw new NotFoundHttpException(sprintf('Draft not found for page %d', $page->getId()));
        }
        $this->entityManager->remove($pageDraft);
        $this->entityManager->flush();
    }

    public function getEntity(int $id): ?PageDraft
    {
        return $this->pageDraftRepository->find($id);
    }

    public function getPermissionBase(): string
    {
        return 'page:pages';
    }
}
