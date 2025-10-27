<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Controller;

use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Company;
use Mautic\SmsBundle\Entity\Sms;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxLookupControllerTest extends MauticMysqlTestCase
{
    public function testCompanyLookupWithOptions(): void
    {
        $company = new Company();
        $company->setName('Test Company');
        $this->em->persist($company);
        $this->em->flush();

        $params = [
            'action'       => 'lead:getLookupChoiceList',
            'searchKey'    => 'lead.company',
            'lead_company' => 'Test',
        ];

        $this->client->request(Request::METHOD_GET, '/s/ajax', $params);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertSame('[{"text":"Test Company","value":"'.$company->getId().'"}]', $response->getContent());
    }

    public function testEmailLookupWithEmailTypeOption(): void
    {
        $templateEmail = new Email();
        $templateEmail->setName('Test Template Email');
        $templateEmail->setEmailType('template');
        $templateEmail->setSubject('Test');
        $templateEmail->setCustomHtml('test');

        $listEmail = new Email();
        $listEmail->setName('Test List Email');
        $listEmail->setEmailType('list');
        $listEmail->setSubject('Test');
        $listEmail->setCustomHtml('test');

        $this->em->persist($templateEmail);
        $this->em->persist($listEmail);
        $this->em->flush();

        $params = [
            'action'     => 'email:getLookupChoiceList',
            'searchKey'  => 'email',
            'email_type' => 'template',
            'email'      => 'Test',
        ];

        $this->client->request(Request::METHOD_GET, '/s/ajax', $params);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), true);

        $this->assertCount(1, $content, 'Should return one group');
        $this->assertCount(1, $content[0]['items'], 'Should return one email');
        $this->assertSame('Test Template Email', $content[0]['items'][(string) $templateEmail->getId()]);
    }

    public function testSmsLookupWithEmailTypeOption(): void
    {
        $templateSms = new Sms();
        $templateSms->setName('Test Template Sms');
        $templateSms->setSmsType('template');
        $templateSms->setMessage('Test');
        $templateSms->setIsPublished(true);


        $listSms = new Sms();
        $listSms->setName('Test list Sms');
        $listSms->setSmsType('list');
        $listSms->setMessage('Test');
        $listSms->setIsPublished(true);

        $this->em->persist($templateSms);
        $this->em->persist($listSms);
        $this->em->flush();

        $params = [
            'action'    => 'sms:getLookupChoiceList',
            'searchKey' => 'sms',
            'sms_type'  => 'template',
            'sms'       => 'sms',
        ];

        $this->client->request(Request::METHOD_GET, '/s/ajax', $params);
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), true);

        $this->assertCount(1, $content, 'Should return one group');
        $this->assertCount(1, $content[0]['items'], 'Should return only sms');
        $this->assertSame('Test Template Sms', $content[0]['items'][(string) $templateSms->getId()]);
    }

    public function testMessageLookupWithOptions(): void
    {
        $channel = new Channel();
        $channel->setChannel('email');
        $channel->setChannelId(12);
        $channel->setIsEnabled(true);

        $message = new Message();
        $message->setName('API message 1');
        $message->addChannel($channel);

        $this->em->persist($channel);
        $this->em->persist($message);
        $this->em->flush();

        $params = [
            'action'       => 'channel:getLookupChoiceList',
            'searchKey'    => 'channel.message',
            'channel_message' => 'message',
        ];

        $this->client->request(Request::METHOD_GET, '/s/ajax', $params);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), true);

        $this->assertCount(1, $content, 'Should return only Message');
        $this->assertSame('API message 1', $content[0]['text']);
    }
}
