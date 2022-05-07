<?php

namespace MauticPlugin\MauticCitrixBundle\Entity;

use MauticPlugin\MauticCitrixBundle\Helper\BasicEnum;

abstract class CitrixEventTypes extends BasicEnum
{
    // Used for querying events
    const STARTED    = 'started';
    const REGISTERED = 'registered';
    const ATTENDED   = 'attended';
}
