<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

/**
 * Class SlotImageType.
 */
class SlotSocialShareType extends SlotType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'slot_socialshare';
    }
}
