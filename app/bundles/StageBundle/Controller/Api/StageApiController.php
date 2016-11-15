<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class PointApiController.
 */
class StageApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('stage');
        $this->entityClass      = 'Mautic\StageBundle\Entity\Stage';
        $this->entityNameOne    = 'stage';
        $this->entityNameMulti  = 'stages';
        $this->permissionBase   = 'stage:stages';
        $this->serializerGroups = ['stageDetails', 'categoryList', 'publishDetails'];
    }
}
