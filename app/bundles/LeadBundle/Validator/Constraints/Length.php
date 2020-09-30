<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Length as SymfonyLength;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Length extends SymfonyLength
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
