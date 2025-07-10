<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailListsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Email) {
            throw new UnexpectedTypeException($value, Email::class);
        }

        if ('list' !== $value->getEmailType() || $value->getTranslationParent()) {
            return;
        }

        $this->validateLists($value);
        $this->validateExcludedLists($value);
        $this->validateConflictingLists($value);
    }

    private function validateLists(Email $email): void
    {
        $violations = $this->context->getValidator()->validate(
            $email->getLists(),
            [
                new LeadListAccess(),
                new NotBlank(
                    [
                        'message' => 'mautic.lead.lists.required',
                    ]
                ),
            ]
        );
        $this->addViolationsAtPath($violations, 'lists');
    }

    private function validateExcludedLists(Email $email): void
    {
        $violations = $this->context->getValidator()->validate(
            $email->getExcludedLists(),
            [
                new LeadListAccess(['allowEmpty' => true]),
            ]
        );
        $this->addViolationsAtPath($violations, 'excludedLists');
    }

    private function validateConflictingLists(Email $email): void
    {
        $listsIds         = $this->getListsIds($email->getLists());
        $excludedListsIds = $this->getListsIds($email->getExcludedLists());
        $isConflicting    = (bool) array_intersect($listsIds, $excludedListsIds);

        if ($isConflicting) {
            $this->context->buildViolation('mautic.lead.excluded_lists.conflicting')
                ->atPath('excludedLists')
                ->addViolation();
        }
    }

    /**
     * @param LeadList[] $lists
     *
     * @return string[]
     */
    private function getListsIds(iterable $lists): array
    {
        $ids = [];

        foreach ($lists as $list) {
            $ids[] = (string) $list->getId();
        }

        return $ids;
    }

    /**
     * @param ConstraintViolationInterface[] $violations
     */
    private function addViolationsAtPath(iterable $violations, string $path): void
    {
        foreach ($violations as $violation) {
            $this->context->buildViolation($violation->getMessage())
                ->atPath($path)
                ->addViolation();
        }
    }
}
