<?php

namespace Mautic\EmailBundle\Model;

use Mautic\CategoryBundle\Entity\Category;

readonly class EmailActionModel
{
    public function __construct(
        private EmailModel $emailModel
    ) {
    }

    public function setEmailsCategory(array $emailsIds, Category $newCategory): void
    {
        $emails = $this->emailModel->getEmailsByIds($emailsIds);

        foreach ($emails as $email) {
            if (!$this->emailModel->canEditEmail($email)) {
                continue;
            }

            $email->setCategory($newCategory);
        }

        $this->emailModel->saveEntities($emails);
    }
}
