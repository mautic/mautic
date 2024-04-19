<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailDraft;
use Mautic\EmailBundle\Entity\EmailDraftRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailDraftModel
{
    public function __construct(
       private EntityManagerInterface $entityManager,
       private EmailDraftRepository $emailDraftRepository
    ) {
    }

    public function createDraft(Email $email, string $html, string $template, bool $publicPreview = true): EmailDraft
    {
        $emailDraft = $this->emailDraftRepository->findOneBy(['email' => $email]);
        if (!is_null($emailDraft)) {
            throw new \Exception(sprintf('Draft already exists for email %d', $email->getId()));
        }
        $emailDraft = new EmailDraft($email, $html, $template, $publicPreview);

        $this->entityManager->persist($emailDraft);
        $this->entityManager->flush();

        return $emailDraft;
    }

    public function deleteDraft(Email $email): void
    {
        if (is_null($emailDraft = $email->getDraft())) {
            throw new NotFoundHttpException(sprintf('Draft not found for email %d', $email->getId()));
        }
        $this->entityManager->remove($emailDraft);
        $this->entityManager->flush();
    }

    public function getEntity(int $id): ?EmailDraft
    {
        return $this->emailDraftRepository->find($id);
    }

    public function getPermissionBase(): string
    {
        return 'email:emails';
    }

    public function getRepository(): EmailDraftRepository
    {
        return $this->emailDraftRepository;
    }
}
