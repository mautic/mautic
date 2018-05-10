<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FieldAliasKeyword extends Constraint
{
    public $message = 'mautic.lead.field.keyword.invalid';

    public function validatedBy()
    {
        return FieldAliasKeywordValidator::class;
    }
}
