<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class HtmlType extends AbstractType
{
    public function getParent(): string
    {
        return 'textarea';
    }

    public function getName(): string
    {
        return 'html';
    }
}
