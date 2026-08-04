<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniqueUserAlias extends Constraint
{
    #[HasNamedArguments]
    public function __construct(
        public string $field,
        public string $message = 'This alias is already in use.',
    ) {
        parent::__construct();
    }

    public function validatedBy(): string
    {
        return 'uniqueleadlist';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    //    public function getRequiredOptions(): array
    //    {
    //        return ['field'];
    //    }

    //    public function getDefaultOption(): string
    //    {
    //        return 'field';
    //    }
}
