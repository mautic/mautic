<?php

namespace Mautic\CoreBundle\Helper\Clickthrough;

class ClickthroughKeyConverter extends AbstractShortKeyConverter
{
    public function __construct()
    {
        $this->shortKeys = [
            'e'    => 'email',
            's'    => 'source',
            'st'   => 'stat',
            'l'    => 'lead',
            'c'    => 'channel',
            'ut'   => 'utmTags',
            'us'   => 'utm_source',
            'um'   => 'utm_medium',
            'uc'   => 'utm_campaign',
            'ucon' => 'utm_content',
        ];
    }
}
