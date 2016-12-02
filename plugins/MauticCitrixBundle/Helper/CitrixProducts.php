<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Helper;

abstract class CitrixProducts extends BasicEnum
{
    const GOTOWEBINAR  = 'webinar';
    const GOTOMEETING  = 'meeting';
    const GOTOTRAINING = 'training';
    const GOTOASSIST   = 'assist';
}
