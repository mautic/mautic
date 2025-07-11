<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class EmailVariantInCampaignFunctionalTest extends MauticMysqlTestCase
{
    public function testMarketingEmailWithVariantShouldBeSentOnce(): void
    {
        $contact = new Lead();
        $contact->setEmail('test@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        $email    = $this->createEmailWithVariant();
        $campaign = $this->createCampaignWithDoubleEmailSent($email->getId());

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);
        $campaign->addLead(0, $campaignLead);

        $this->em->flush();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        Assert::assertStringContainsString('2 total events(s) to be processed in batches', $commandResult->getDisplay());

        $variant = $email->getVariantChildren()->first();

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        $countVariantSent = $emailStatRepository->count([
            'email' => $variant->getId(),
            'lead'  => $contact->getId(),
        ]);

        // the email should be sent only one to the contact
        $this->assertEquals(1, $countVariantSent);
    }

    private function createEmailWithVariant(): Email
    {
        $email = new Email();
        $email->setName('Email Parent');
        $email->setSubject('Email Parent subject');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);
        $this->em->flush();

        $variant = new Email();
        $variant->setName('Email Variant');
        $variant->setSubject('Email Variant subject');
        $variant->setEmailType('template');
        $variant->setIsPublished(true);
        $variant->setVariantParent($email);
        $variant->setVariantSettings(['weight' => 100, 'winnerCriteria' => 'email.openrate']);
        $email->addVariantChild($variant);

        $this->em->persist($email);
        $this->em->persist($variant);
        $this->em->flush();

        return $email;
    }

    /**
     * Creates campaign that will try to send the same marketing email twice.
     *
     * Campaign diagram:
     *          -------------------
     *          -  Start segment  -
     *          -------------------
     *         |                 |
     * --------------------  ------------------
     * -   Send email     -  -  Send email    -
     * --------------------  ------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithDoubleEmailSent(int $emailId): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Update contact');

        $this->em->persist($campaign);
        $this->em->flush();

        $emailSend1 = new Event();
        $emailSend1->setCampaign($campaign);
        $emailSend1->setName('Send email');
        $emailSend1->setType('email.send');
        $emailSend1->setChannel('email');
        $emailSend1->setChannelId($emailId);
        $emailSend1->setEventType('action');
        $emailSend1->setTriggerMode('immediate');
        $emailSend1->setOrder(1);
        $emailSend1->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '549',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'email'      => $emailId,
                    'email_type' => 'marketing',
                    'priority'   => '2',
                    'attempts'   => '3',
                ],
                'type'            => 'email.send',
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'email'      => $emailId,
                'email_type' => 'marketing',
                'priority'   => 2,
                'attempts'   => 3.0,
            ]
        );

        $this->em->persist($emailSend1);
        $this->em->flush();

        $emailSend2 = new Event();
        $emailSend2->setCampaign($campaign);
        $emailSend2->setName('Send email 2');
        $emailSend2->setType('email.send');
        $emailSend2->setChannel('email');
        $emailSend2->setChannelId($emailId);
        $emailSend2->setEventType('action');
        $emailSend2->setTriggerMode('immediate');
        $emailSend2->setOrder(1);
        $emailSend2->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '849',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'email'      => $emailId,
                    'email_type' => 'marketing',
                    'priority'   => '2',
                    'attempts'   => '3',
                ],
                'type'            => 'email.send',
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'email'      => $emailId,
                'email_type' => 'marketing',
                'priority'   => 2,
                'attempts'   => 3.0,
            ]
        );
        $this->em->persist($emailSend2);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $emailSend2->getId(),
                        'positionX' => '849',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => $emailSend1->getId(),
                        'positionX' => '549',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $emailSend1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'lists',
                        'targetId' => $emailSend2->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $campaign->addEvent(0, $emailSend1);
        $campaign->addEvent(1, $emailSend2);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }
}
