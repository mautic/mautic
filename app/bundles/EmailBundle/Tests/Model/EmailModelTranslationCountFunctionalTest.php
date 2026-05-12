<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

final class EmailModelTranslationCountFunctionalTest extends MauticMysqlTestCase
{
    private const CONTACT_COUNT = 10;

    public function testEmailTranslationCount(): void
    {
        $contacts = $this->createContacts();
        $email    = $this->createEmailWithTranslation();
        $campaign = $this->createCampaignWithEmailSent($email->getId());

        $this->addContactsToCampaign($contacts, $campaign);
        $this->em->clear();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        $this->assertStringContainsString('10 total events(s) to be processed in batches', $commandResult->getDisplay());
        $this->em->clear();

        $emailModel = static::getContainer()->get('mautic.email.model.email');
        \assert($emailModel instanceof EmailModel);

        // Re-fetch the email to get the updated stats
        $email = $emailModel->getEntity($email->getId());
        \assert($email instanceof Email);

        $this->assertEquals(self::CONTACT_COUNT, $email->getSentCount(true));
    }

    /**
     * @param Lead[] $contacts
     */
    private function addContactsToCampaign(array $contacts, Campaign $campaign): void
    {
        foreach ($contacts as $key => $contact) {
            $campaignLead = new CampaignLead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setLead($contact);
            $campaignLead->setDateAdded(new \DateTime());
            $this->em->persist($campaignLead);
            $campaign->addLead($key, $campaignLead);
        }

        $this->em->flush();
    }

    /**
     * @return Lead[]
     */
    private function createContacts(): array
    {
        $contactModel = static::getContainer()->get('mautic.lead.model.lead');
        \assert($contactModel instanceof LeadModel);

        $contacts = [];

        $languages = ['hi', 'fr', 'en'];

        for ($i = 0; $i < self::CONTACT_COUNT; ++$i) {
            $index  = random_int(0, count($languages) - 1);
            $locale = $languages[$index];

            $contact = new Lead();
            $contact->setEmail("test{$i}@some.email");
            $contact->addUpdatedField('preferred_locale', $locale);

            $contactModel->saveEntity($contact);
            $contacts[] = $contact;
        }

        return $contacts;
    }

    private function createEmailWithTranslation(): Email
    {
        $email = new Email();
        $email->setName('Email English');
        $email->setSubject('Email Parent subject');
        $email->setCustomHtml('some content');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $email->setLanguage('en');
        $this->em->persist($email);

        $translationVariantHindi = new Email();
        $translationVariantHindi->setName('Email in Hindi');
        $translationVariantHindi->setSubject('Email in Hindi');
        $translationVariantHindi->setCustomHtml('some content');
        $translationVariantHindi->setEmailType('template');
        $translationVariantHindi->setIsPublished(true);
        $translationVariantHindi->setLanguage('hi');
        $translationVariantHindi->setTranslationParent($email);
        $this->em->persist($translationVariantHindi);

        $translationVariantFrench = new Email();
        $translationVariantFrench->setName('Email in French');
        $translationVariantFrench->setCustomHtml('some content');
        $translationVariantFrench->setSubject('Email in French');
        $translationVariantFrench->setEmailType('template');
        $translationVariantFrench->setIsPublished(true);
        $translationVariantFrench->setLanguage('fr');
        $translationVariantFrench->setTranslationParent($email);
        $this->em->persist($translationVariantFrench);

        $email->addTranslationChild($translationVariantHindi);
        $email->addTranslationChild($translationVariantFrench);

        $this->em->flush();

        return $email;
    }

    private function createCampaignWithEmailSent(int $emailId): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Update contact');
        $this->em->persist($campaign);

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Send email');
        $event->setType('email.send');
        $event->setChannel('email');
        $event->setChannelId($emailId);
        $event->setEventType('action');
        $event->setTriggerMode('immediate');
        $event->setOrder(1);
        $event->setProperties(
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
                '_token'          => 'test_token',
                'buttons'         => [
                    'save' => '',
                ],
                'email'      => $emailId,
                'email_type' => 'marketing',
                'priority'   => 2,
                'attempts'   => 3.0,
            ]
        );
        $this->em->persist($event);

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event->getId(),
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
                        'targetId' => $event->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $campaign->addEvent(0, $event);

        $this->em->flush();

        return $campaign;
    }
}
