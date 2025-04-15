<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class LeadFieldMinimumLengthValidator extends ConstraintValidator
{
    public function __construct(private Connection $connection)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof LeadField) {
            throw new UnexpectedTypeException($value, LeadField::class);
        }

        if (!$constraint instanceof LeadFieldMinimumLength) {
            throw new UnexpectedTypeException($constraint, LeadFieldMinimumLength::class);
        }

        if ($value->isNew() || !$value->supportsLength() || !$value->getCharLengthLimit()) {
            return;
        }

        $maxCharacterLengthInUse = $this->getMaxCharacterLengthInUse($value);

        if ($value->getCharLengthLimit() >= $maxCharacterLengthInUse) {
            return;
        }

        $this->context->buildViolation($constraint->message, ['%length%' => $maxCharacterLengthInUse])
            ->atPath('charLengthLimit')
            ->addViolation();
    }

    private function getMaxCharacterLengthInUse(LeadField $leadField): int
    {
        try {
            return (int) $this->connection->createQueryBuilder()
                ->select('MAX(CHAR_LENGTH('.$leadField->getAlias().'))')
                ->from(MAUTIC_TABLE_PREFIX.$leadField->getCustomFieldObject())
                ->executeQuery()
                ->fetchOne();
        } catch (InvalidFieldNameException) {
            return 0;
        }
    }
}
