<?php

namespace Mautic\EmailBundle\Model;

use Mautic\CategoryBundle\Entity\Category;

readonly class EmailActionModel
{
    public function __construct(
        private EmailModel $emailModel
    ) {
    }

    public function setCategory(array $emailsIds, Category $newCategory): array
    {
        $emails = $this->emailModel->getByIds($emailsIds);

        $affected = [];

        foreach ($emails as $email) {
            if (!$this->emailModel->canEdit($email)) {
                continue;
            }

            $email->setCategory($newCategory);
            $affected[] = $email;
        }

        if ($affected) {
            $this->emailModel->saveEntities($emails);
        }

        return $affected;
    }
}
