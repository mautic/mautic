<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class EmailImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private EmailModel $emailModel,
        private EntityManagerInterface $entityManager,
        private EmailRepository $emailRepository,
        private FormRepository $formRepository,
        private PageRepository $pageRepository,
        private EventDispatcherInterface $dispatcher,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onEmailExport', 0],
            EntityImportEvent::class        => ['onEmailImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onEmailExport(EntityExportEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $emailId = $event->getEntityId();
        $email   = $this->emailModel->getEntity($emailId);
        if (!$email) {
            return;
        }

        $emailData = [
            'id'                   => $email->getId(),
            'translation_parent_id'=> $email->getTranslationParent()?->getId(),
            'variant_parent_id'    => $email->getVariantParent()?->getId(),
            'unsubscribeform_id'   => $email->getUnsubscribeForm()?->getId(),
            'preference_center_id' => $email->getPreferenceCenter()?->getId(),
            'is_published'         => $email->getIsPublished(),
            'name'                 => $email->getName(),
            'description'          => $email->getDescription(),
            'subject'              => $email->getSubject(),
            'preheader_text'       => $email->getPreheaderText(),
            'from_name'            => $email->getFromName(),
            'use_owner_as_mailer'  => $email->getUseOwnerAsMailer(),
            'template'             => $email->getTemplate(),
            'content'              => $email->getContent(),
            'utm_tags'             => $email->getUtmTags(),
            'plain_text'           => $email->getPlainText(),
            'custom_html'          => $email->getCustomHtml(),
            'email_type'           => $email->getEmailType(),
            'publish_up'           => $email->getPublishUp()?->format(DATE_ATOM),
            'publish_down'         => $email->getPublishDown()?->format(DATE_ATOM),
            'revision'             => $email->getRevision(),
            'lang'                 => $email->getLanguage(),
            'variant_settings'     => $email->getVariantSettings(),
            'variant_start_date'   => $email->getVariantStartDate()?->format(DATE_ATOM),
            'dynamic_content'      => $email->getDynamicContent(),
            'headers'              => $email->getHeaders(),
            'public_preview'       => $email->getPublicPreview(),
            'uuid'                 => $email->getUuid(),
        ];

        $event->addEntity(Email::ENTITY_NAME, $emailData);
        $this->logAction('export', $emailId, $emailData);

        $assets = $email->getAssetAttachments();
        foreach ($assets as $asset) {
            $subEvent = new EntityExportEvent(Asset::ENTITY_NAME, (int) $asset->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addEntities($subEvent->getEntities());
            $event->addDependencyEntity(Email::ENTITY_NAME, [
                Email::ENTITY_NAME  => $emailId,
                Asset::ENTITY_NAME  => (int) $asset->getId(),
            ]);
        }

        $form = $email->getUnsubscribeForm();
        if ($form) {
            $subEvent = new EntityExportEvent(Form::ENTITY_NAME, (int) $form->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addEntities($subEvent->getEntities());
            $event->addDependencyEntity(Email::ENTITY_NAME, [
                Email::ENTITY_NAME => $emailId,
                Form::ENTITY_NAME  => (int) $form->getId(),
            ]);
        }
        $page = $email->getPreferenceCenter();
        if ($page) {
            $subEvent = new EntityExportEvent(Page::ENTITY_NAME, (int) $page->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addEntities($subEvent->getEntities());
            $event->addDependencyEntity(Email::ENTITY_NAME, [
                Email::ENTITY_NAME => $emailId,
                Page::ENTITY_NAME  => (int) $page->getId(),
            ]);
        }
    }

    public function onEmailImport(EntityImportEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $email = $this->emailRepository->findOneBy(['uuid' => $element['uuid']]);
            $isNew = !$email;

            $email ??= new Email();
            $unsubscribeForm = !empty($element['unsubscribeform_id'])
                ? $this->formRepository->find($element['unsubscribeform_id'])
                : null;

            $preferenceCenter = !empty($element['preference_center_id'])
                ? $this->pageRepository->find($element['preference_center_id'])
                : null;

            $email->setUnsubscribeForm($unsubscribeForm);
            $email->setPreferenceCenter($preferenceCenter);

            $this->serializer->denormalize(
                $element,
                Email::class,
                null,
                ['object_to_populate' => $email]
            );

            // The serializer's MetadataAwareNameConverter does not map
            // custom_html → customHtml (the Doctrine column name differs
            // from the property name), so the imported email's compiled HTML
            // was silently lost. Set it manually to preserve the body and
            // satisfy the MJML save validation.
            if (isset($element['custom_html']) && !$email->getCustomHtml()) {
                $email->setCustomHtml($element['custom_html']);
            }

            $this->emailModel->saveEntity($email);

            $event->addEntityIdMap((int) $element['id'], $email->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $email->getName();
            $stats[$status]['ids'][]   = $email->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $email->getId(), $element);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Email::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->emailRepository->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Email::class]);
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        $this->performDuplicationCheck(
            $event,
            Email::ENTITY_NAME,
            Email::class,
            'name',
            $this->entityManager
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'email',
            'object'    => 'email',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
