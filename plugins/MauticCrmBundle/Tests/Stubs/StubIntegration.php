<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Stubs;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

class StubIntegration extends CrmAbstractIntegration
{
    public function getName()
    {
        return 'Stub';
    }
}
