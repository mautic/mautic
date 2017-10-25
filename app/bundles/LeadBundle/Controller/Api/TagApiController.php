<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\Tag;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class TagApiController.
 */
class TagApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->getModel('lead.tag');
        $this->entityClass     = Tag::class;
        $this->entityNameOne   = 'tag';
        $this->entityNameMulti = 'tags';

        parent::initialize($event);
    }
}
