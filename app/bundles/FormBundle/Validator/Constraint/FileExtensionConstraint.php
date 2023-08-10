<?php

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

class FileExtensionConstraint extends Constraint
{
    public $message = 'File extension contains an illegal extension: "{{ forbidden }}".';
}
