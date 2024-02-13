<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Entity\LeadNoteRepository;
use Mautic\LeadBundle\Event\LeadNoteEvent;
use Mautic\LeadBundle\Form\Type\NoteType;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<LeadNote>
 */
class NoteModel extends FormModel
{
    public function __construct(
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
        private RequestStack $requestStack
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): LeadNoteRepository
    {
        return $this->em->getRepository(LeadNote::class);
    }

    public function getPermissionBase(): string
    {
        return 'lead:notes';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?LeadNote
    {
        if (null === $id) {
            return new LeadNote();
        }

        return parent::getEntity($id);
    }

    /**
     * @param string|null $action
     * @param array       $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof LeadNote) {
            throw new MethodNotAllowedHttpException(['LeadNote']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(NoteType::class, $entity, $options);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
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

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function getNoteCount(Lead $lead, $useFilters = false)
    {
        $filter   = ($useFilters) ? $this->getSession()->get('mautic.lead.'.$lead->getId().'.note.filter', '') : null;
        $noteType = ($useFilters) ? $this->getSession()->get('mautic.lead.'.$lead->getId().'.notetype.filter', []) : null;

        return $this->getRepository()->getNoteCount($lead->getId(), $filter, $noteType);
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
