<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ReportBundle\Tests\Functional\AbstractReportSubscriberTestCase;

class ReportSubscriberFunctionalTest extends AbstractReportSubscriberTestCase
{
    public function testAssetDownloadReportWithDncListColumn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->createDnc('email', $leads[2], DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        $asset = $this->createAsset();
        $this->emulateAssetDownload($asset, $leads[0]);
        $this->emulateAssetDownload($asset, $leads[1]);
        $this->emulateAssetDownload($asset, $leads[2]);

        $report = $this->createReport(
            source: 'asset.downloads',
            columns: ['l.id', 'a.id', 'a.title', 'dnc_preferences'],
            filters: [
                [
                    'column'    => 'dnc_preferences',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'in',
                    'value'     => [
                        'email:'.DoNotContact::UNSUBSCRIBED,
                        'email:'.DoNotContact::BOUNCED,
                    ],
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            [(string) $leads[0]->getId(), (string) $asset->getId(), $asset->getTitle(), 'DNC Bounced: Email'],
            [(string) $leads[2]->getId(), (string) $asset->getId(), $asset->getTitle(), 'DNC Manually Unsubscribed: Text Message, DNC Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    private function createAsset(): Asset
    {
        $asset = new Asset();
        $asset->setTitle('test');
        $asset->setAlias('test');
        $asset->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $asset->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $asset->setCreatedByUser('Test User');

        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }

    private function emulateAssetDownload(Asset $asset, Lead $contact): Download
    {
        $assetDownload = new Download();
        $assetDownload->setAsset($asset);
        $assetDownload->setLead($contact);
        $assetDownload->setDateDownload(new \DateTime());
        $assetDownload->setCode(200);
        $assetDownload->setTrackingId(random_int(1, 99999));
        $this->em->persist($assetDownload);
        $this->em->flush();

        return $assetDownload;
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    public function createDnc(string $channel, Lead $contact, int $reason): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setChannel($channel);
        $dnc->setLead($contact);
        $dnc->setReason($reason);
        $dnc->setDateAdded(new \DateTime());
        $this->em->persist($dnc);

        return $dnc;
    }
}
