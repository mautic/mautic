<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class HtmlType extends AbstractType
{
    public function getParent(): string
    {
        return TextareaType::class;
    }
}
