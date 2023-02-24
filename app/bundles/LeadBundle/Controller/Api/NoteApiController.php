<?php

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Model\NoteModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<LeadNote>
 */
class NoteApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(ControllerEvent $event)
    {
        $leadNoteModel = $this->getModel('lead.note');
        \assert($leadNoteModel instanceof NoteModel);

        $this->model            = $leadNoteModel;
        $this->entityClass      = LeadNote::class;
        $this->entityNameOne    = 'note';
        $this->entityNameMulti  = 'notes';
        $this->serializerGroups = ['leadNoteDetails', 'leadList'];

        // When a user passes in a note like "This is <strong>text</strong>", this will
        // keep the HTML that was passed in.
        $this->dataInputMasks = ['text' => 'html'];

        parent::initialize($event);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\Lead &$entity
     * @param                                $parameters
     * @param                                $form
     * @param string                         $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (!empty($parameters['lead'])) {
            $lead = $this->checkLeadAccess($parameters['lead'], $action);

            if ($lead instanceof Response) {
                return $lead;
            }

            $entity->setLead($lead);
            unset($parameters['lead']);
        } elseif ('new' === $action) {
            return $this->returnError('lead ID is mandatory', Response::HTTP_BAD_REQUEST);
        }
    }
}
