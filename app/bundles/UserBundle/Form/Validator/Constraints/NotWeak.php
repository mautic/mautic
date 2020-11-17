<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NotWeak extends Constraint
{
    public const TOO_WEAK = 'f61e730a-284e-11eb-adc1-0242ac120002';

    protected static $errorNames = [
        self::TOO_WEAK => 'PASSWORD_TOO_WEAK_ERROR',
    ];

    public $message = 'This password is too weak. Consider using a stronger password.';

    public $score;
}
