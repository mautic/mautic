<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

final class ProjectApiController extends CommonApiController
{
    public function initialize(ControllerEvent|FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('project');
        $this->entityClass      = Project::class;
        $this->entityNameOne    = 'project';
        $this->entityNameMulti  = 'projects';
        $this->permissionBase   = $this->model->getPermissionBase();
        parent::initialize($event);
    }
}
