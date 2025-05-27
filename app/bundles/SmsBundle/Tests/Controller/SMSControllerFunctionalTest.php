<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\SmsBundle\Entity\Sms;
use PHPUnit\Framework\Assert;

final class SMSControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://localhost';
        parent::setUp();
    }

    public function testSmsWithProject(): void
    {
        $sms = $this->CreateSms();

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/sms/edit/'.$sms->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['sms[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedSms = $this->em->find(Sms::class, $sms->getId());
        Assert::assertSame($project->getId(), $savedSms->getProjects()->first()->getId());
    }

    private function CreateSms(string $name = 'sms', string $message = 'sms body'): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage($message);
        $sms->setSmsType('template');
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }
}
