<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticMapperBundle
 *
 * @package Mautic\MapperBundle
 */
class MauticMapperBundle extends Bundle
{

}

//import api libraries
require_once __DIR__.'/Libraries/Salesforce/SalesforceApi.php';
require_once __DIR__.'/Libraries/SugarCRM/SugarCRMApi.php';
require_once __DIR__.'/Libraries/vTigerCRM/vTigerCRMApi.php';
require_once __DIR__.'/Libraries/ZohoCRM/ZohoCRMApi.php';