<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class ActionRemoveDoNotContact extends AbstractType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_action_removedonotcontact';
    }
}
