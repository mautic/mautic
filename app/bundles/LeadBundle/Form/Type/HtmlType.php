<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class HtmlType extends AbstractType
{
    public function getParent(): string
    {
        return TextareaType::class;
    }
}
