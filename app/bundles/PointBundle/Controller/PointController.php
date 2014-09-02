<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

class PointController extends CommonController
{
    public function __construct()
    {
        $this->permissionName = "points";
        $this->modelName      = "point";
        $this->sessionVar     = "point";
        $this->translationVar = "point";
        $this->routeVar       = "point";
        $this->templateVar    = "Point";
        $this->actionVar      = "pointaction";
        $this->mauticContent  = "point";
        $this->tableAlias     = "p";
    }
}