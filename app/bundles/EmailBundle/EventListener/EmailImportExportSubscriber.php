<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EmailImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailModel $emailModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
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
            'translation_parent_id'=> $email->getTranslationParent(),
            'variant_parent_id'    => $email->getVariantParent(),
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

        $form = $email->getUnsubscribeForm();
        if ($form) {
            $subEvent = new EntityExportEvent(Form::ENTITY_NAME, (int) $form->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addEntities($subEvent->getEntities());
            $event->addDependencyEntity(Email::ENTITY_NAME, [
                Email::ENTITY_NAME => (int) $emailId,
                Form::ENTITY_NAME  => (int) $form->getId(),
            ]);
        }
        $page = $email->getPreferenceCenter();
        if ($page) {
            $subEvent = new EntityExportEvent(Page::ENTITY_NAME, (int) $page->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addEntities($subEvent->getEntities());
            $event->addDependencyEntity(Email::ENTITY_NAME, [
                Email::ENTITY_NAME => (int) $emailId,
                Page::ENTITY_NAME  => (int) $page->getId(),
            ]);
        }
    }

    public function onEmailImport(EntityImportEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $userName = '';
        if ($event->getUserId()) {
            $user     = $this->userModel->getEntity($event->getUserId());
            $userName = $user ? $user->getFirstName().' '.$user->getLastName() : '';
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $email = $this->entityManager->getRepository(Email::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew = !$email;

            $email ??= new Email();
            $isNew && $email->setDateAdded(new \DateTime());
            $email->setDateModified(new \DateTime());
            $email->setUuid($element['uuid']);
            $email->setCreatedByUser($userName);
            if (!$isNew) {
                $email->setModifiedByUser($userName);
            }

            $email->setTranslationParent($element['translation_parent_id'] ?? null);
            $email->setVariantParent($element['variant_parent_id'] ?? null);

            $unsubscribeForm = !empty($element['unsubscribeform_id'])
                ? $this->entityManager->getRepository(Form::class)->find($element['unsubscribeform_id'])
                : null;

            $preferenceCenter = !empty($element['preference_center_id'])
                ? $this->entityManager->getRepository(Page::class)->find($element['preference_center_id'])
                : null;

            $email->setUnsubscribeForm($unsubscribeForm);
            $email->setPreferenceCenter($preferenceCenter);

            $email->setIsPublished((bool) ($element['is_published'] ?? false));
            $email->setName($element['name'] ?? '');
            $email->setDescription($element['description'] ?? '');
            $email->setSubject($element['subject'] ?? '');
            $email->setPreheaderText($element['preheader_text'] ?? '');
            $email->setFromName($element['from_name'] ?? '');
            $email->setUseOwnerAsMailer((bool) ($element['use_owner_as_mailer'] ?? false));
            $email->setTemplate($element['template'] ?? '');
            $email->setContent($element['content'] ?? '');
            $email->setUtmTags($element['utm_tags'] ?? '');
            $email->setPlainText($element['plain_text'] ?? '');
            $email->setCustomHtml($element['custom_html'] ?? '');
            $email->setEmailType($element['email_type'] ?? '');
            $email->setPublishUp($element['publish_up'] ?? null);
            $email->setPublishDown($element['publish_down'] ?? null);
            $email->setRevision((int) ($element['revision'] ?? 0));
            $email->setLanguage($element['lang'] ?? '');
            $email->setVariantSettings($element['variant_settings'] ?? []);
            $email->setVariantStartDate($element['variant_start_date'] ?? null);
            $email->setDynamicContent($element['dynamic_content'] ?? '');
            $email->setHeaders($element['headers'] ?? '');
            $email->setPublicPreview($element['public_preview'] ?? '');

            $this->entityManager->persist($email);
            $this->entityManager->flush();

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
            $entity = $this->entityManager->getRepository(Email::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Email::class]);
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $item) {
            $existing = $this->entityManager->getRepository(Email::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][] = $existing->getName();
                $summary[EntityImportEvent::UPDATE]['ids'][]   = $existing->getId();
                ++$summary[EntityImportEvent::UPDATE]['count'];
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['name'];
                ++$summary[EntityImportEvent::NEW]['count'];
            }
        }

        foreach ($summary as $type => $data) {
            if ($data['count'] > 0) {
                $event->setSummary($type, [Email::ENTITY_NAME => $data]);
            }
        }
    }

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
