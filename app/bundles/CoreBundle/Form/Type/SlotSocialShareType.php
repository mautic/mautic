<?php

namespace Mautic\CoreBundle\Form\Type;

class SlotSocialShareType extends SlotType
{
    public function getBlockPrefix(): string
    {
        return 'slot_socialshare';
    }
}
