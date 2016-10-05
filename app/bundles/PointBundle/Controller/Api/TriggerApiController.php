<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class TriggerApiController.
 */
class TriggerApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('point.trigger');
        $this->entityClass      = 'Mautic\PointBundle\Entity\Trigger';
        $this->entityNameOne    = 'trigger';
        $this->entityNameMulti  = 'triggers';
        $this->permissionBase   = 'point:triggers';
        $this->serializerGroups = ['triggerDetails', 'categoryList', 'publishDetails'];
    }
}
