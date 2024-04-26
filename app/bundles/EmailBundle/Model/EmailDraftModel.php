<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailDraft;
use Mautic\EmailBundle\Entity\EmailDraftRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailDraftModel extends AbstractCommonModel
{
    public function createDraft(Email $email, string $html, string $template, bool $publicPreview = true): EmailDraft
    {
        $emailDraft = $this->getRepository()->findOneBy(['email' => $email]);
        if (!is_null($emailDraft)) {
            throw new \Exception(sprintf('Draft already exists for email %d', $email->getId()));
        }
        $emailDraft = new EmailDraft($email, $html, $template, $publicPreview);

        $this->em->persist($emailDraft);
        $this->em->flush();

        return $emailDraft;
    }

    public function deleteDraft(Email $email): void
    {
        if (is_null($emailDraft = $email->getDraft())) {
            throw new NotFoundHttpException(sprintf('Draft not found for email %d', $email->getId()));
        }
        $this->em->remove($emailDraft);
        $this->em->flush();
    }

    public function getPermissionBase(): string
    {
        return 'email:emails';
    }

    public function getRepository(): EmailDraftRepository
    {
        return $this->em->getRepository(EmailDraft::class);
    }
}
