<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

class RangeController extends CommonController
{
    public function __construct()
    {
        $this->permissionName = "ranges";
        $this->modelName      = "point.range";
        $this->sessionVar     = "pointrange";
        $this->translationVar = "point.range";
        $this->routerVar      = "pointrange";
        $this->templateVar    = "Range";
        $this->actionVar      = "pointrangeaction";
        $this->mauticContent  = "pointRange";
        $this->tableAlias     = "r";
    }
}