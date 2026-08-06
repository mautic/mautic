<?php

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Entity\LeadNoteRepository;
use Mautic\LeadBundle\Event\LeadNoteEvent;
use Mautic\LeadBundle\Form\Type\NoteType;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<LeadNote>
 */
final class NoteModel extends FormModel
{
    private RequestStack $requestStack;

    private LeadNoteRepository $leadNoteRepository;

    #[Required]
    public function autowireNoteModel(
        RequestStack $requestStack,
        LeadNoteRepository $leadNoteRepository,
    ): void {
        $this->requestStack       = $requestStack;
        $this->leadNoteRepository = $leadNoteRepository;
    }

    public function getRepository(): LeadNoteRepository
    {
        return $this->leadNoteRepository;
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

    public function createForm($entity, mixed ...$args): FormInterface
    {
        [$action, $options] = $this->resolveCreateFormArgs($args);

        if (!$entity instanceof LeadNote) {
            throw new MethodNotAllowedHttpException(['LeadNote']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(NoteType::class, $entity, $options);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
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
            if (!$event instanceof Event) {
                $event = new LeadNoteEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    public function getNoteCount(Lead $lead, $useFilters = false): int
    {
        $viewPermissions = $this->security->isGranted(['lead:notes:viewown', 'lead:notes:viewother'], 'RETURN_ARRAY');
        $canViewOwn      = $viewPermissions['lead:notes:viewown'] ?? false;
        $canViewOther    = $viewPermissions['lead:notes:viewother'] ?? false;

        if (!$canViewOwn && !$canViewOther) {
            return 0;
        }

        $filter    = ($useFilters) ? $this->requestStack->getSession()->get('mautic.lead.'.$lead->getId().'.note.filter', '') : null;
        $noteType  = ($useFilters) ? $this->requestStack->getSession()->get('mautic.lead.'.$lead->getId().'.notetype.filter', []) : null;
        $createdBy = $canViewOther ? null : $this->userHelper->getUser()?->getId();

        return $this->leadNoteRepository->getNoteCount($lead->getId(), $filter, $noteType, $createdBy);
    }
}
