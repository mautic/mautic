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

final class SmsSubscriberTokenTest extends MauticMysqlTestCase
{
    use SmsTestHelperTrait;

    protected function setUp(): void
    {
        $this->configParams['sms_disable_trackable_urls'] = false;

        parent::setUp();
    }

    public function testSmsTokenReplacement(): void
    {
        $transport = $this->configureTwilioWithArrayTransport();
        /** @var SmsModel $smsModel */
        $smsModel  = $this->getContainer()->get(SmsModel::class);
        $this->assertInstanceOf(SmsModel::class, $smsModel);

        /** @var LeadModel $contactModel */
        $contactModel = $this->getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $contactModel);

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

        $this->assertCount(1, $transport->smses);

        $ctRegex        = 'ct=([a-zA-Z0-9%]+)';
        $domainRegex    = 'https?:\/\/([a-zA-Z0-9.-]+)';
        $assetLinkRegex = $domainRegex.'\/asset\/'.$asset->getSlug().'\?'.$ctRegex;
        $pageLinkRegex  = $domainRegex.'\/test-page\?'.$ctRegex;
        $trackingRegex  = $domainRegex.'\/r\/([a-zA-Z0-9]+)\?'.$ctRegex;

        $this->assertMatchesRegularExpression("/Hello John, download {$assetLinkRegex} or visit {$pageLinkRegex} or {$trackingRegex}/", $transport->smses[0]['content']);
    }
}
