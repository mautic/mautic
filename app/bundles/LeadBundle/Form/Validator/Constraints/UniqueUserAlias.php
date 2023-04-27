<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 */
class UniqueUserAlias extends Constraint
{
    public string $message = 'This alias is already in use.';
    public string $field   = '';

    public function __construct(string $message, string $field)
    {
        $this->message = $message;
        $this->field   = $field;

        parent::__construct([
            'field'   => 'alias',
            'message' => 'mautic.lead.list.alias.unique',
        ]);
    }

    public function validatedBy()
    {
        return 'uniqueleadlist';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getRequiredOptions()
    {
        return ['field'];
    }

    public function getDefaultOption()
    {
        return 'field';
    }
}
