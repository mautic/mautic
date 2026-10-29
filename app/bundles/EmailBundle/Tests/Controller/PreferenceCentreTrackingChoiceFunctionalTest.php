<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

/**
 * A contact can turn tracking of their activity off and on again from the preference centre.
 */
final class PreferenceCentreTrackingChoiceFunctionalTest extends MauticMysqlTestCase
{
    private const FIELD = 'lead_contact_frequency_rules[allow_activity_tracking]';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['show_contact_preferences'] = 1;

        parent::setUp();
    }

    public function testContactTurnsTrackingOffInTheDefaultPreferenceCentre(): void
    {
        [$contact, $stat] = $this->createSentEmail();

        $crawler = $this->client->request(Request::METHOD_GET, $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Allow my activity to be recorded and associated with my profile', (string) $this->client->getResponse()->getContent());

        $form = $crawler->filter('form')->form();
        $this->assertSame('1', $form[self::FIELD]->getValue(), 'tracking starts as allowed');
        $form[self::FIELD]->setValue('0');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $preference = $this->findPreference($contact);
        $this->assertInstanceOf(TrackingPreference::class, $preference);
        $this->assertFalse($preference->isAllowed());
        $this->assertSame(TrackingPreference::SOURCE_PREFERENCE_CENTRE, $preference->getSource());

        // The email open pixel now follows the choice
        $this->client->request(Request::METHOD_GET, '/email/'.$stat->getTrackingHash().'.gif', [], [], ['REMOTE_ADDR' => '203.0.113.80']);
        $this->em->clear();
        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->getIsRead());
    }

    public function testSavingWithoutChangingTheChoiceRecordsNothing(): void
    {
        [$contact, $stat] = $this->createSentEmail();

        $crawler = $this->client->request(Request::METHOD_GET, $this->buildUnsubscribeUrl($stat));
        $this->client->submit($crawler->filter('form')->form());
        $this->assertResponseIsSuccessful();

        $this->assertNotInstanceOf(TrackingPreference::class, $this->findPreference($contact), 'a contact who never chose keeps no recorded choice');
    }

    public function testContactTurnsTrackingBackOn(): void
    {
        [$contact, $stat] = $this->createSentEmail(false);

        $crawler = $this->client->request(Request::METHOD_GET, $this->buildUnsubscribeUrl($stat));
        $form    = $crawler->filter('form')->form();
        $this->assertSame('0', $form[self::FIELD]->getValue());
        $form[self::FIELD]->setValue('1');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $preference = $this->findPreference($contact);
        $this->assertInstanceOf(TrackingPreference::class, $preference);
        $this->assertTrue($preference->isAllowed());
    }

    public function testContactTurnsTrackingOffInALandingPagePreferenceCentre(): void
    {
        [$contact, $stat] = $this->createSentEmail(null, '{trackingpreference}{saveprefsbutton}');

        $crawler = $this->client->request(Request::METHOD_GET, $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.pref-trackingpreference'));

        $form = $crawler->filter('form')->form();
        $form[self::FIELD]->setValue('0');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $preference = $this->findPreference($contact);
        $this->assertInstanceOf(TrackingPreference::class, $preference);
        $this->assertFalse($preference->isAllowed());
    }

    public function testALandingPageWithoutTheChoiceKeepsTheContactsChoice(): void
    {
        [$contact, $stat] = $this->createSentEmail(false, '{segmentlist}{saveprefsbutton}');

        $crawler = $this->client->request(Request::METHOD_GET, $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('.pref-trackingpreference'));
        $this->assertCount(0, $crawler->filter('input[name="'.self::FIELD.'"]'), 'the choice isn\'t added to the page');

        $this->client->submit($crawler->filter('form')->form());
        $this->assertResponseIsSuccessful();

        $preference = $this->findPreference($contact);
        $this->assertInstanceOf(TrackingPreference::class, $preference);
        $this->assertFalse($preference->isAllowed(), 'the choice made earlier is kept');
    }

    /**
     * @return array{Lead, Stat}
     */
    private function createSentEmail(?bool $allowsTracking = null, ?string $preferenceCentreHtml = null): array
    {
        $contact = new Lead();
        $contact->setEmail('choice@example.com');
        $this->em->persist($contact);

        $preferenceCentre = null;
        if (null !== $preferenceCentreHtml) {
            $preferenceCentre = new Page();
            $preferenceCentre->setTitle('Preference centre');
            $preferenceCentre->setAlias('preference-centre');
            $preferenceCentre->setTemplate('blank');
            $preferenceCentre->setIsPreferenceCenter(true);
            $preferenceCentre->setIsPublished(true);
            $preferenceCentre->setCustomHtml('<html><body>'.$preferenceCentreHtml.'</body></html>');
            $this->em->persist($preferenceCentre);
        }

        $email = new Email();
        $email->setName('Tracking choice');
        $email->setSubject('Tracking choice');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->setPreferenceCenter($preferenceCentre);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setTrackingHash('tracking_choice_hash');
        $stat->setEmailAddress('choice@example.com');
        $stat->setLead($contact);
        $stat->setDateSent((new \DateTime())->modify('-80 second'));
        $stat->setEmail($email);
        $this->em->persist($stat);
        $this->em->flush();

        if (null !== $allowsTracking) {
            self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($contact, $allowsTracking, 'test');
        }

        $this->logoutUser();

        return [$contact, $stat];
    }

    private function findPreference(Lead $contact): ?TrackingPreference
    {
        $this->em->clear();

        return $this->em->getRepository(TrackingPreference::class)->findOneBy(['lead' => $contact->getId(), 'purpose' => TrackingPreference::PURPOSE_ACTIVITY]);
    }

    private function buildUnsubscribeUrl(Stat $stat): string
    {
        $hash = self::getContainer()->get(MailHashHelper::class)->getEmailHash((string) $stat->getEmailAddress());

        return "/email/unsubscribe/{$stat->getTrackingHash()}/{$stat->getEmailAddress()}/{$hash}";
    }
}
