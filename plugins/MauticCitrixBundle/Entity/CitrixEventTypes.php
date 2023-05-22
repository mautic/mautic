<?php

namespace MauticPlugin\MauticCitrixBundle\Entity;

use MauticPlugin\MauticCitrixBundle\Helper\BasicEnum;

abstract class CitrixEventTypes extends BasicEnum
{
    // Used for querying events
    public const STARTED    = 'started';
    public const REGISTERED = 'registered';
    public const ATTENDED   = 'attended';
}
