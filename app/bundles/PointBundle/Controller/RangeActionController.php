<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

class RangeActionController extends CommonActionController
{

    public function __construct()
    {
        $this->permissionName = "ranges";
        $this->actionVar      = "pointrangeaction";
        $this->modelName      = "point.range";
        $this->formName       = "pointrangeaction";
        $this->templateVar    = "Range";
        $this->mauticContent  = "pointRangeAction";
        $this->routeVar       = "pointrangeaction";
        $this->entityClass    = "RangeAction";
    }
}