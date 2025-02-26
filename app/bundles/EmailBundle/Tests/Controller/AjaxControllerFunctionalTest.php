<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email as EmailMime;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testSendTestEmailAction(): void
    {
        /** @var CoreParametersHelper $parameters */
        $parameters = self::getContainer()->get('mautic.helper.core_parameters');

        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:sendTestEmail');
        Assert::assertTrue($this->client->getResponse()->isOk());

        $this->assertQueuedEmailCount(0, message: 'Test emails should never be queued.');
        $this->assertEmailCount(1);

        $email = KernelTestCase::getMailerMessage();
        \assert($email instanceof EmailMime);

        /** @var UserHelper $userHelper */
        $userHelper = static::getContainer()->get(UserHelper::class);
        $user       = $userHelper->getUser();

        Assert::assertSame('Mautic test email', $email->getSubject());
        Assert::assertSame('Hi! This is a test email from Mautic. Testing...testing...1...2...3!', $email->getTextBody());
        Assert::assertCount(1, $email->getFrom());
        Assert::assertSame($parameters->get('mailer_from_name'), $email->getFrom()[0]->getName());
        Assert::assertSame($parameters->get('mailer_from_email'), $email->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email->getTo());
        Assert::assertSame($user->getFirstName().' '.$user->getLastName(), $email->getTo()[0]->getName());
        Assert::assertSame($user->getEmail(), $email->getTo()[0]->getAddress());
    }

    public function testGetDeliveredCount(): void
    {
        $contact1 = $this->createContact('john@example.com');
        $contact2 = $this->createContact('paul@example.com');

        $this->em->flush();
        $email   = $this->createEmailWithParams(
            'Email A',
            'Email A Subject',
            'list',
            'beefree-empty',
            'Test html'
        );
        $this->em->flush();

        $this->createEmailStat($contact1, $email);
        $this->createEmailStat($contact2, $email);
        $email->setSentCount(2);
        $this->em->persist($email);
        $this->em->flush();

        $this->createDoNotContact($contact2, $email, DoNotContact::BOUNCED);
        $this->em->flush();

        $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/ajax?action=email:getEmailDeliveredCount&id={$email->getId()}");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertSame([
            'success'         => 1,
            'delivered'       => 1,
        ], json_decode($response->getContent(), true));
    }

    public function testGetDeliveredCountWithTranslations(): void
    {
        $contactEn1 = $this->createContact('john@example.com');
        $contactEn2 = $this->createContact('paul@example.com');
        $contactPl1 = $this->createContact('szczepan@example.com');
        $contactPl2 = $this->createContact('jadwiga@example.com');
        $this->em->flush();

        $emailEn   = $this->createEmailWithParams(
            'Email EN',
            'Email EN Subject',
            'list',
            'beefree-empty',
            'Test html EN'
        );
        $emailEn->setLanguage('en');
        $this->em->flush();

        $emailPl   = $this->createEmailWithParams(
            'Email PL',
            'Email PL Subject',
            'list',
            'beefree-empty',
            'Test html PL'
        );
        $emailEn->setLanguage('pl_PL');
        $this->em->persist($emailPl);
        $this->em->flush();

        $emailPl->setTranslationParent($emailEn);
        $emailEn->addTranslationChild($emailPl);
        $this->createEmailStat($contactEn1, $emailEn);
        $this->createEmailStat($contactEn2, $emailEn);
        $this->createEmailStat($contactPl1, $emailPl);
        $this->createEmailStat($contactPl2, $emailPl);
        $emailEn->setSentCount(2);
        $emailPl->setSentCount(2);
        $this->em->persist($emailEn);
        $this->em->persist($emailPl);
        $this->em->flush();

        $this->createDoNotContact($contactEn1, $emailEn, DoNotContact::BOUNCED);
        $this->createDoNotContact($contactPl1, $emailPl, DoNotContact::BOUNCED);
        $this->em->flush();
        $this->em->clear();

        $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/ajax?action=email:getEmailDeliveredCount&id={$emailEn->getId()}");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertSame([
            'success'         => 1,
            'delivered'       => 1,
        ], json_decode($response->getContent(), true));
    }

    public function testHeatmapAction(): void
    {
        $contacts = [
            $this->createContact('john@example.com'),
            $this->createContact('paul@example.com'),
        ];

        $this->em->flush();
        $email   = $this->createEmailWithParams(
            'Email A',
            'Email A Subject',
            'list',
            'beefree-empty',
            'Test html'
        );
        $this->em->flush();

        $this->createEmailStat($contacts[0], $email);
        $this->createEmailStat($contacts[1], $email);
        $email->setSentCount(2);
        $this->em->flush();
        $this->em->persist($email);

        $trackables = [
            $this->createTrackable('https://example.com/1', $email->getId()),
            $this->createTrackable('https://example.com/2', $email->getId()),
        ];
        $this->em->flush();

        $this->emulateLinkClick($email, $trackables[0], $contacts[0], 3);
        $this->emulateLinkClick($email, $trackables[1], $contacts[0]);
        $this->emulateLinkClick($email, $trackables[1], $contacts[1]);
        $this->em->flush();

        $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/ajax?action=email:heatmap&id={$email->getId()}");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('Test html', $content['content']);
        $this->assertSame([
            [
                'redirect_id'      => $trackables[0]->getRedirect()->getRedirectId(),
                'url'              => 'https://example.com/1',
                'id'               => (string) $trackables[0]->getRedirect()->getId(),
                'hits'             => '3',
                'unique_hits'      => '1',
                'unique_hits_rate' => 0.3333,
                'unique_hits_text' => '1 click',
                'hits_rate'        => 0.6,
                'hits_text'        => '3 clicks',
            ],
            [
                'redirect_id'      => $trackables[1]->getRedirect()->getRedirectId(),
                'url'              => 'https://example.com/2',
                'id'               => (string) $trackables[1]->getRedirect()->getId(),
                'hits'             => '2',
                'unique_hits'      => '2',
                'unique_hits_rate' => 0.6667,
                'unique_hits_text' => '2 clicks',
                'hits_rate'        => 0.4,
                'hits_text'        => '2 clicks',
            ],
        ], $content['clickStats']);
        $this->assertSame(3, $content['totalUniqueClicks']);
        $this->assertSame(5, $content['totalClicks']);
    }

    /**
     * Test email lookup with name with special chars.
     */
    public function testEmailGetLookupChoiceListAction(): void
    {
        $emailName = 'It\'s an email';
        $email     = new Email();
        $email->setName($emailName);
        $email->setSubject('Email Subject');
        $email->setEmailType('template');
        $this->em->persist($email);
        $this->em->flush($email);

        $payload = [
            'action'     => 'email:getLookupChoiceList',
            'email_type' => 'template',
            'top_level'  => 'variant',
            'searchKey'  => 'email',
            'email'      => $emailName,
        ];

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertNotEmpty($response);
        $this->assertEquals($emailName, $response[0]['items'][$email->getId()]);
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createEmailStat(Lead $contact, Email $email): Stat
    {
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contact);
        $emailStat->setEmailAddress($contact->getEmail());
        $emailStat->setDateSent(new \DateTime());
        $this->em->persist($emailStat);

        return $emailStat;
    }

    private function createDoNotContact(Lead $contact, Email $email, int $reason): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setLead($contact);
        $dnc->setChannel('email');
        $dnc->setChannelId($email->getId());
        $dnc->setDateAdded(new \DateTime());
        $dnc->setReason($reason);
        $dnc->setComments('Test DNC');
        $this->em->persist($dnc);

        return $dnc;
    }

    /**
     * @param array<int, mixed> $segments
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function createEmailWithParams(string $name, string $subject, string $emailType, string $template, string $customHtml, array $segments = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setLists($segments);
        $this->em->persist($email);

        return $email;
    }

    private function createTrackable(string $url, int $channelId, int $hits = 0, int $uniqueHits = 0): Trackable
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

    private function emulateLinkClick(Email $email, Trackable $trackable, Lead $lead, int $count = 1): void
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
            $pageHit->setDateHit(new \DateTime());
            $pageHit->setCode(200);
            $pageHit->setUrl($redirect->getUrl());
            $pageHit->setTrackingId($redirect->getRedirectId());
            $pageHit->setSource('email');
            $pageHit->setSourceId($email->getId());
            $this->em->persist($pageHit);
        }
    }
}
