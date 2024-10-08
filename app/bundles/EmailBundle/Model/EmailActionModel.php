<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;

class EmailActionModel
{
    public function __construct(
        private EmailModel $emailModel,
        private EmailRepository $emailRepository,
        private CorePermissions $corePermissions,
    ) {
    }

    /**
     * @param array<int> $emailsIds
     *
     * @return array<Email>
     */
    public function setCategory(array $emailsIds, Category $newCategory): array
    {
        $emails = $this->emailRepository->findBy(['id' => $emailsIds]);

        $affected = [];

        foreach ($emails as $email) {
            if (!$this->canEdit($email)) {
                continue;
            }

            $email->setCategory($newCategory);
            $affected[] = $email;
        }

        if ($affected) {
            $this->saveEntities($emails);
        }

        return $affected;
    }

    private function canEdit(Email $email): bool
    {
        return $this->corePermissions->hasEntityAccess('email:emails:editown', 'email:emails:editother', $email->getCreatedBy());
    }

    /**
     * @param array<Email> $emails
     */
    private function saveEntities(array $emails): void
    {
        $this->emailModel->saveEntities($emails);
    }
}
