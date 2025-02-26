<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class DbRegexValidator extends ConstraintValidator
{
    public function __construct(private Connection $connection)
    {
    }

    public function validate(mixed $regex, Constraint $constraint): void
    {
        if (!$constraint instanceof DbRegex) {
            throw new UnexpectedTypeException($constraint, DbRegex::class);
        }

        try {
            $this->connection->executeQuery('SELECT 1 REGEXP ? AS is_valid', [$regex]);
        } catch (Exception $e) {
            $this->context->buildViolation(
                $this->stripUglyPartOfTheErrorMessage($e->getPrevious()->getMessage())
            )->addViolation();
        }
    }

    private function stripUglyPartOfTheErrorMessage(string $message): string
    {
        return preg_replace('/SQLSTATE\[\d+\]: [\w ]+: \d+ /', '', $message);
    }
}
