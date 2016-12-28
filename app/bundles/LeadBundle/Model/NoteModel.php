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

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Event\LeadNoteEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class NoteModel
 * {@inheritdoc}
 */
class NoteModel extends FormModel
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadNote');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:notes';
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
            return new LeadNote();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof LeadNote) {
            throw new MethodNotAllowedHttpException(['LeadNote']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('leadnote', $entity, $options);
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
        if (!$entity instanceof LeadNote) {
            throw new MethodNotAllowedHttpException(['LeadNote']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::NOTE_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::NOTE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::NOTE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::NOTE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadNoteEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Lead $lead
     * @param      $useFilters
     *
     * @return mixed
     */
    public function getNoteCount(Lead $lead, $useFilters = false)
    {
        $filter   = ($useFilters) ? $this->session->get('mautic.lead.'.$lead->getId().'.note.filter', '') : null;
        $noteType = ($useFilters) ? $this->session->get('mautic.lead.'.$lead->getId().'.notetype.filter', []) : null;

        return $this->getRepository()->getNoteCount($lead->getId(), $filter, $noteType);
    }
}
