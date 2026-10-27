<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * @extends AbstractType<mixed>
 */
final class ConfigFileType extends AbstractType
{
    public function getParent(): string
    {
        return FileType::class;
    }
}
