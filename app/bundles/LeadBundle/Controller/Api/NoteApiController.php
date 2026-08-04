<?php

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Model\NoteModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<LeadNote>
 */
final class NoteApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        UserHelper $userHelper,
        NoteModel $leadNoteModel,
    ) {
        $this->model            = $leadNoteModel;
        $this->entityClass      = LeadNote::class;
        $this->entityNameOne    = 'note';
        $this->entityNameMulti  = 'notes';
        $this->permissionBase   = 'lead:notes';
        $this->serializerGroups = ['leadNoteDetails', 'leadList'];

        // When a user passes in a note like "This is <strong>text</strong>", this will
        // keep the HTML that was passed in.
        $this->dataInputMasks = ['text' => 'html'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $userHelper);
    }

    /**
     * @param LeadNote             $entity
     * @param FormInterface<mixed> $form
     * @param array<mixed>         $parameters
     * @param string               $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (!empty($parameters['lead'])) {
            $leadAction = 'new' === $action ? 'view' : $action;
            $lead       = $this->checkLeadAccess($parameters['lead'], $leadAction);

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
