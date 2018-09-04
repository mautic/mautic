<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Phone number constraint.
 */
class PhoneNumberConstraint extends Constraint
{
    public $message       = null;

    public function getMessage()
    {
        if (null !== $this->message) {
            return $this->message;
        }
    }
}
