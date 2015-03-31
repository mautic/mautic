<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class TriggerApiController
 */
class TriggerApiController extends CommonApiController
{

    /**
     * {@inheritdoc}
     */
    public function initialize (FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->factory->getModel('point.trigger');
        $this->entityClass      = 'Mautic\PointBundle\Entity\Point';
        $this->entityNameOne    = 'trigger';
        $this->entityNameMulti  = 'triggers';
        $this->permissionBase   = 'point:triggers';
        $this->serializerGroups = array('triggerDetails', 'categoryList', 'publishDetails');
    }

    /**
     * Obtains a list of triggers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction ()
    {
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific trigger
     *
     * @param int $id Point ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction ($id)
    {
        return parent::getEntityAction($id);
    }
}