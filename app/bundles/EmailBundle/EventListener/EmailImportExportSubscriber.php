<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EmailImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailModel $emailModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onEmailExport', 0],
            EntityImportEvent::class => ['onEmailImport', 0],
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
        ];

        $event->addEntity(Email::ENTITY_NAME, $emailData);
    }

    public function onEmailImport(EntityImportEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();
        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('<comment>User ID '.$userId.' not found. Emails will not have a created_by_user field set.</comment>');
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $email = new Email();

            $email->setTranslationParent($element['translation_parent_id'] ?? null);
            $email->setVariantParent($element['variant_parent_id'] ?? null);
            $email->setUnsubscribeForm($element['unsubscribeform_id'] ?? null);
            $email->setPreferenceCenter($element['preference_center_id'] ?? null);
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
            $email->setDateAdded(new \DateTime());
            $email->setDateModified(new \DateTime());
            $email->setCreatedByUser($userName);
            $this->entityManager->persist($email);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $email->getId());
            $output->writeln('<info>Imported email: '.$email->getName().' with ID: '.$email->getId().'</info>');
        }
    }
}
