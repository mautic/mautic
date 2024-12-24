<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;

class EmailActionModel
{
    public const LOAD_RESULTS_IN_CHUNKS_OF = 200;

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
        // Do this in chunks so that we don't run out of memory.
        $chunks = array_chunk($emails, self::LOAD_RESULTS_IN_CHUNKS_OF);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $email) {
                if (!$this->canEdit($email)) {
                    continue;
                }
                $email->setCategory($newCategory);
                $affected[] = $email;
            }
            // Clear the chunk from memory after each iteration.
            unset($chunk);
        }

        if ($affected) {
            $this->saveEntities($emails);
        }

        return $affected;
    }

    public function getEmails($filter): array
    {
        return $this->emailModel->getEntities([
            'filter'           => $filter,
            'ignore_paginator' => true,
        ]);
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
