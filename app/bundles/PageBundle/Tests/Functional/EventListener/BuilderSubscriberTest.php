<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\EventListener;

use DateTime;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BuilderSubscriberTest extends AbstractMauticTestCase
{
    const TOKEN_SELECTOR       = '#lead_contact_frequency_rules__token';
    const SAVE_BUTTON_SELECTOR = '#lead_contact_frequency_rules_buttons_save';
    const FORM_SELECTOR        = 'form[name="lead_contact_frequency_rules"]';

    public function testOnPageDisplayCorrectlyWrapsAllFrequencyFormInputsIncludingTokenAndSaveButton(): void
    {
        $emailStat = $this->createStat(
            $email = $this->createEmail(),
            $lead  = $this->createLead()
        );

        $this->em->flush();

        $unsubscribeUrl = $this->router->generate('mautic_email_unsubscribe', [
            'idHash'     => $emailStat->getTrackingHash(),
            'urlEmail'   => $lead->getEmail(),
            'secretHash' => $this->container->get('mautic.helper.mailer_hash')->getEmailHash($lead->getEmail()),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        $crawler = $this->client->request('GET', $unsubscribeUrl);
        $form    = $crawler->filter(static::FORM_SELECTOR);

        Assert::assertCount(1, $form->filter(static::TOKEN_SELECTOR), sprintf('The following HTML does not contain the _token. %s', $form->html()));
        Assert::assertCount(1, $form->filter(static::SAVE_BUTTON_SELECTOR), sprintf('The following HTML does not contain the save button. %s', $form->html()));
    }

    private function createStat(Email $email, Lead $lead): Stat
    {
        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($lead);
        $stat->setEmailAddress($lead->getEmail());
        $stat->setDateSent(new DateTime());
        $stat->setTrackingHash(uniqid());
        $this->em->persist($stat);

        return $stat;
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Example');
        $email->setPreferenceCenter($this->createPage());
        $this->em->persist($email);

        return $email;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('test@example.com');
        $this->em->persist($lead);

        return $lead;
    }

    private function createPage(): Page
    {
        $page = new Page();
        $page->setTitle('Preference Center');
        $page->setAlias('preference-center');
        $page->setIsPreferenceCenter(true);
        $page->setContent($this->getPageContent());
        $page->setIsPublished(true);
        $this->em->persist($page);

        return $page;
    }

    private function getPageContent(): string
    {
        return <<<PAGE
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <title>{pagetitle}</title>
</head>
<body>
    <div>
        <div data-slot="channelfrequency"></div>
    </div>
    <div>
        <div data-slot="saveprefsbutton"></div>
    </div>
</body>
</html>
PAGE;
    }
}
