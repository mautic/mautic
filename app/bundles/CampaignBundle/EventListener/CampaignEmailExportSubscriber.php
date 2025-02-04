<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignEmailExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_CAMPAIGN_EMAIL => ['onCampaignEmailExport', 0],
        ];
    }

    public function onCampaignEmailExport(EntityExportEvent $event): void
    {
        $campaignId = $event->getEntityId();

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $results = $queryBuilder
            ->select('channel_id')
            ->from('campaign_events')
            ->where('campaign_id = :campaignId')
            ->andWhere('channel = :channel')
            ->andWhere('channel_id IS NOT NULL')
            ->andWhere('channel_id != 0')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('channel', 'email')
            ->executeQuery()
            ->fetchAllAssociative();

        $channelIds = array_column($results, 'channel_id');

        $emails = $this->entityManager->getRepository(Email::class)->findBy(['id' => $channelIds]);

        $emailData          = [];

        foreach ($emails as $email) {
            $emailData = [
                'id'               => $email->getId(),
                'subject'          => $email->getSubject(),
                'is_published'     => $email->getIsPublished(),
                'name'             => $email->getName(),
                'description'      => $email->getDescription(),
                'from_address'     => $email->getFromAddress(),
                'from_name'        => $email->getFromName(),
                'reply_to_address' => $email->getReplyToAddress(),
                'preheader_text'   => $email->getPreheaderText(),
                'bcc_address'      => $email->getBccAddress(),
                'template'         => $email->getTemplate(),
                'content'          => $email->getContent(),
                'utm_tags'         => $email->getUtmTags(),
                'plain_text'       => $email->getPlainText(),
                'custom_html'      => $email->getCustomHtml(),
                'email_type'       => $email->getEmailType(),
                'lang'             => $email->getLanguage(),
                'variant_settings' => $email->getVariantSettings(),
                'dynamic_content'  => $email->getDynamicContent(),
                'headers'          => $email->getHeaders(),
            ];
            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_EMAIL, $emailData);
        }
    }
}
