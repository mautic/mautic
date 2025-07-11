<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;

final class EmailFixturesHelper
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param array<int, mixed> $segments
     */
    public function createEmail(
        string $name = 'Test email',
        string $subject = 'Test subject',
        string $emailType = 'template',
        bool $isPublished = true,
        string $template = 'blank',
        string $customHtml = 'Test Html',
        array $segments = [],
    ): Email {
        $email = (new Email())
            ->setName($name)
            ->setSubject($subject)
            ->setEmailType($emailType)
            ->setIsPublished($isPublished)
            ->setTemplate($template)
            ->setCustomHtml($customHtml);

        if (!empty($segments)) {
            $email->setLists($segments);
        }

        $this->em->persist($email);

        return $email;
    }

    public function emulateEmailSend(Lead $contact, Email $email, string $date = 'now', ?string $source = null, ?int $sourceId = null): Stat
    {
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contact);
        $emailStat->setEmailAddress($contact->getEmail());
        $emailStat->setDateSent(new \DateTime($date));
        if ($source && $sourceId) {
            $emailStat->setSource($source);
            $emailStat->setSourceId($sourceId);
        }
        $email->setSentCount($email->getSentCount() + 1);

        $this->em->persist($emailStat);
        $this->em->persist($email);

        return $emailStat;
    }

    public function emulateEmailRead(Stat $emailStat, Email $email, string $date = 'now'): Stat
    {
        $emailStat->setIsRead(true);
        $emailStat->setDateRead(new \DateTime($date));
        $email->setReadCount($email->getReadCount() + 1);
        $this->em->persist($emailStat);
        $this->em->persist($email);

        return $emailStat;
    }

    public function createEmailLink(string $url, int $channelId, int $hits = 0, int $uniqueHits = 0): Trackable
    {
        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl($url);
        $redirect->setHits($hits);
        $redirect->setUniqueHits($uniqueHits);
        $this->em->persist($redirect);

        $trackable = new Trackable();
        $trackable->setChannelId($channelId);
        $trackable->setChannel('email');
        $trackable->setHits($hits);
        $trackable->setUniqueHits($uniqueHits);
        $trackable->setRedirect($redirect);
        $this->em->persist($trackable);

        return $trackable;
    }

    public function emulateLinkClick(Email $email, Trackable $trackable, Lead $lead, string $date = 'now', int $count = 1): void
    {
        $trackable->setHits($trackable->getHits() + $count);
        $trackable->setUniqueHits($trackable->getUniqueHits() + 1);
        $this->em->persist($trackable);

        $redirect = $trackable->getRedirect();

        $ip = new IpAddress();
        $ip->setIpAddress('127.0.0.1');
        $this->em->persist($ip);

        for ($i = 0; $i < $count; ++$i) {
            $pageHit = new Hit();
            $pageHit->setRedirect($redirect);
            $pageHit->setEmail($email);
            $pageHit->setLead($lead);
            $pageHit->setIpAddress($ip);
            $pageHit->setDateHit(new \DateTime($date));
            $pageHit->setCode(200);
            $pageHit->setUrl($redirect->getUrl());
            $pageHit->setTrackingId($redirect->getRedirectId());
            $pageHit->setSource('email');
            $pageHit->setSourceId($email->getId());
            $this->em->persist($pageHit);
        }
    }
}
