<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SegmentInUse extends Constraint
{
    public $message = 'mautic.lead_list.is_in_use';

    public function validatedBy()
    {
        return 'segment_in_use';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
