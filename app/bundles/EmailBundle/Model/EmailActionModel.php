<?php

namespace Mautic\EmailBundle\Model;

use Mautic\CategoryBundle\Entity\Category;

/**
 * @todo add tests
 */
readonly class EmailActionModel
{
    public function __construct(
        private EmailModel $emailModel
    ) {
    }

    public function setCategory(array $emailsIds, Category $newCategory): void
    {
        $emails = $this->emailModel->getByIds($emailsIds);

        foreach ($emails as $email) {
            if (!$this->emailModel->canEdit($email)) {
                continue;
            }

            $email->setCategory($newCategory);
        }

        $this->emailModel->saveEntities($emails);
    }
}
