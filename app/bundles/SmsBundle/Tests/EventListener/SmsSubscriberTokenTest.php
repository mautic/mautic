<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Tests\SmsTestHelperTrait;
use PHPUnit\Framework\Assert;

final class SmsSubscriberTokenTest extends MauticMysqlTestCase
{
    use SmsTestHelperTrait;

    public function testSmsTokenReplacement(): void
    {
        $transport = $this->configureTwilioWithArrayTransport();
        $smsModel  = $this->getContainer()->get('mautic.sms.model.sms');
        \assert($smsModel instanceof SmsModel);

        $contactModel = $this->getContainer()->get('mautic.lead.model.lead');
        \assert($contactModel instanceof LeadModel);

        $page = new Page();
        $page->setTitle('Test Page');
        $page->setAlias('test-page');

        $this->em->persist($page);

        $asset = new Asset();
        $asset->setPath('test.jpg');
        $asset->setTitle('test');
        $asset->setAlias('test');

        $this->em->persist($asset);

        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setPhone('1234567890');

        $this->em->persist($contact);
        $this->em->flush();

        $sms = new Sms();
        $sms->setName('Test SMS');
        $sms->setMessage("Hello {contactfield=firstname}, download {assetlink={$asset->getId()}} or visit {pagelink={$page->getId()}} or https://mautic.org");

        $smsModel->saveEntity($sms);
        $smsModel->sendSms($sms, $contactModel->getEntity($contact->getId()));

        Assert::assertCount(1, $transport->smses);

        $ctRegex        = 'ct=([a-zA-Z0-9%]+)';
        $domainRegex    = 'https?:\/\/([a-zA-Z0-9.-]+)';
        $assetLinkRegex = $domainRegex.'\/asset\/'.$asset->getId().':test\?'.$ctRegex;
        $pageLinkregex  = $domainRegex.'\/test-page\?'.$ctRegex;
        $trackingRegex  = $domainRegex.'\/r\/([a-zA-Z0-9]+)\?'.$ctRegex;

        Assert::assertMatchesRegularExpression(
            "/Hello John, download {$assetLinkRegex} or visit {$pageLinkregex} or {$trackingRegex}/",
            $transport->smses[0]['content']
        );
    }
}
