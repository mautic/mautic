<?php

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
