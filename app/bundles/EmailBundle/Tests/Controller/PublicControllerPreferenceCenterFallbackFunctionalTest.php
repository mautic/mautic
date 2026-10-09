<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

final class PublicControllerPreferenceCenterFallbackFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testUnsubscribeUsesCurrentGlobalPreferenceCenterForEmailsWithoutExplicitPreferenceCenter(): void
    {
        $this->configParams['show_contact_preferences'] = 1;

        $defaultA = $this->createPreferenceCenterPage('default-a', '<html><body>Default A {saveprefsbutton}</body></html>');
        $defaultB = $this->createPreferenceCenterPage('default-b', '<html><body>Default B {saveprefsbutton}</body></html>');

        $lead = new Lead();
        $lead->setEmail('john@doe.email');
        $this->em->persist($lead);

        $stat = $this->createSentEmailStat('tracking_hash_global_preference_center', $lead, 'Fallback preference center email');

        $this->assertUnsubscribePageContains($stat, $defaultA->getId(), 'Default A');
        $this->assertUnsubscribePageContains($stat, $defaultB->getId(), 'Default B', 'Default A');
    }

    public function testUnsubscribeServesTranslatedPreferenceCenterBasedOnContactLocale(): void
    {
        $this->configParams['show_contact_preferences'] = 1;

        $parent = $this->createPreferenceCenterPage('pref-center-en', '<html><body>English Preference Center {saveprefsbutton}</body></html>', 'en');
        $child  = $this->createPreferenceCenterPage('pref-center-it', '<html><body>Italian Preference Center {saveprefsbutton}</body></html>', 'it');
        $child->setTranslationParent($parent);
        $parent->addTranslationChild($child);

        $lead = new Lead();
        $lead->setEmail('contact@example.email');
        $lead->addUpdatedField('preferred_locale', 'it');

        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        $leadModel->saveEntity($lead);

        $stat = $this->createSentEmailStat('tracking_hash_translation_preference_center', $lead, 'Translation preference center email');

        $this->assertUnsubscribePageContains($stat, $parent->getId(), 'Italian Preference Center', 'English Preference Center');
    }

    private function createPreferenceCenterPage(string $alias, string $html, ?string $language = null): Page
    {
        $page = new Page();
        $page->setTitle($alias);
        $page->setAlias($alias);
        $page->setTemplate('blank');
        $page->setIsPreferenceCenter(true);
        $page->setIsPublished(true);
        $page->setCustomHtml($html);
        if (null !== $language) {
            $page->setLanguage($language);
        }
        $this->em->persist($page);

        return $page;
    }

    private function createSentEmailStat(string $trackingHash, Lead $lead, string $emailName): Stat
    {
        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress((string) $lead->getEmail());
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $this->em->persist($stat);

        $this->em->flush();

        return $stat;
    }

    private function assertUnsubscribePageContains(Stat $stat, int $preferenceCenterId, string $expectedHtml, ?string $unexpectedHtml = null): void
    {
        $this->setUpSymfony(array_merge($this->configParams, [
            'email_default_preference_center_id' => $preferenceCenterId,
        ]));

        $urlEmail   = $stat->getEmailAddress();
        $secretHash = self::getContainer()->get(MailHashHelper::class)->getEmailHash($urlEmail);
        $crawler    = $this->client->request(Request::METHOD_GET, '/email/unsubscribe/'.$stat->getTrackingHash().'/'.$urlEmail.'/'.$secretHash);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($expectedHtml, $crawler->html());
        if (null !== $unexpectedHtml) {
            $this->assertStringNotContainsString($unexpectedHtml, $crawler->html());
        }
    }
}
