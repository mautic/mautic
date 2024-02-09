<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\SmsBundle\Entity\Sms;

class SmsControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testSmsListView(): void
    {
        $sms = $this->createSms('ABC', 'content of sms', 'list');

        $this->em->persist($sms);
        $this->em->flush();
        $this->em->detach($sms);

        $this->client->request('GET', '/s/sms');
        $clientResponse  = $this->client->getResponse();
        $responseContent = $clientResponse->getContent();
        $this->assertTrue($clientResponse->isOk());

        $routeAlias = 'sms';
        $column     = 'stats';
        $this->assertStringContainsString(
            'col-'.$routeAlias.'-'.$column,
            $responseContent,
            'The return must contain the stats column'
        );

        $this->assertStringContainsString(
            'sms_sent:1',
            $responseContent,
            'The return must contain sms_sent:1'
        );

        $this->assertStringNotContainsStringg(
            'sms_delivered:1',
            $responseContent,
            'The return must not contain sms_sent:1'
        );

        $this->assertStringNotContainsStringg(
            'sms_read:1',
            $responseContent,
            'The return must not contain sms_read:1'
        );

        $this->assertStringNotContainsStringg(
            'sms_failed:1',
            $responseContent,
            'The return must not contain sms_failed:1'
        );
    }

    private function createSms(string $name, string $message, string $smsType): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage($message);
        $sms->setSmsType($smsType);
        $this->em->persist($sms);

        return $sms;
    }
}
