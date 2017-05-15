<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Event\ImportEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ImportModel.
 */
class ImportModel extends FormModel
{
    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * ImportModel constructor.
     *
     * @param PathsHelper $pathsHelper
     */
    public function __construct(
        PathsHelper $pathsHelper
    ) {
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Import');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:imports';
    }

    /**
     * Returns a full path to a dir with unique name based on time.
     *
     * @return string
     */
    public function getUniqueDir()
    {
        $tmpDir = $this->pathsHelper->getSystemPath('tmp', true);

        return $tmpDir.'/imports/'.(new DateTimeHelper())->toUtcString('YmdHis');
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Import();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Import) {
            throw new MethodNotAllowedHttpException(['Import']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::IMPORT_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::IMPORT_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::IMPORT_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::IMPORT_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ImportEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }
}
