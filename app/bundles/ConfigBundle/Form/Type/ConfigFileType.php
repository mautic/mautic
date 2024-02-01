<?php

namespace Mautic\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * @extends AbstractType<mixed>
 */
class ConfigFileType extends AbstractType
{
    public function getParent()
    {
        return FileType::class;
    }
}
