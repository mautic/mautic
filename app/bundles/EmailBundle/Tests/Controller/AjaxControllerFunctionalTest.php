<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
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

        $this->client->request(Request::METHOD_GET, "/s/ajax?action=email:getEmailDeliveredCount&id={$email->getId()}", [], [], $this->createAjaxHeaders());
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

        $this->client->request(Request::METHOD_GET, "/s/ajax?action=email:getEmailDeliveredCount&id={$emailEn->getId()}", [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertSame([
            'success'         => 1,
            'delivered'       => 1,
        ], json_decode($response->getContent(), true));
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
     * @param array<integer, mixed> $segments
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
}
