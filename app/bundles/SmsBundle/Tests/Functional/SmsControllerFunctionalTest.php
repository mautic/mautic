<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\SmsBundle\Entity\Sms;
use Symfony\Component\HttpFoundation\Request;

final class SmsControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testSmsCanBeCreatedWithTranslationParent(): void
    {
        // Create a parent SMS.
        $parentSms = $this->createAnSms('Parent SMS', 'Parent SMS message');

        // Create a child SMS and set the parent.
        $this->client->request(Request::METHOD_GET, '/s/sms/new');
        $this->assertResponseIsSuccessful();

        $form = $this->client->getCrawler()->selectButton('Save')->form();

        $form['sms[name]']                      = 'Child SMS';
        $form['sms[message]']                   = 'Child SMS message';
        $form['sms[translationParentSelector]'] = $parentSms->getId();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert the relationship.
        $childSms = $this->em->getRepository(Sms::class)->findOneBy(['name' => 'Child SMS']);
        $this->assertInstanceOf(Sms::class, $childSms);
        $this->assertSame($parentSms->getId(), $childSms->getTranslationParent()->getId());
    }

    public function testSmsCannotBeItsOwnTranslationParent(): void
    {
        // Create an SMS.
        $sms = $this->createAnSms('Test SMS', 'Test SMS message');

        // Go to the edit page of the SMS.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$sms->getId());
        $this->assertResponseIsSuccessful();

        // Assert that the dropdown does not contain the SMS itself.
        $options = $crawler->filter('#sms_translationParentSelector option');
        $this->assertCount(2, $options);
        $this->assertSame('Choose a translated item...', $options->eq(0)->text());
        $this->assertSame('Create new...', $options->eq(1)->text());
    }

    public function testSmsWithTranslationParentCanBeEdited(): void
    {
        // Create a parent SMS.
        $parentSms = $this->createAnSms('Parent SMS', 'Parent SMS message');

        // Create a child SMS and set the parent.
        $childSms = $this->createAnSms('Child SMS', 'Child SMS message');
        $childSms->setTranslationParent($parentSms);
        $this->em->flush();

        // Create a new potential parent SMS.
        $newParentSms = $this->createAnSms('New Parent SMS', 'New Parent SMS message');

        // Go to the edit page of the child SMS.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$childSms->getId());
        $this->assertResponseIsSuccessful();

        // Assert that the dropdown has the original parent selected.
        $this->assertSame((string) $parentSms->getId(), $crawler->filter('#sms_translationParentSelector')->filter('option[selected]')->attr('value'));

        // Change the translation parent to the new parent.
        $form = $this->client->getCrawler()->selectButton('Save')->form();

        $form['sms[translationParentSelector]'] = $newParentSms->getId();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert that the translation parent has been updated.
        $this->em->clear();
        $updatedChildSms = $this->em->getRepository(Sms::class)->find($childSms->getId());
        $this->assertSame($newParentSms->getId(), $updatedChildSms->getTranslationParent()->getId());
    }

    public function testTranslationParentCanBeRemovedFromSms(): void
    {
        // Create a parent SMS.
        $parentSms = $this->createAnSms('Parent SMS', 'Parent SMS message');

        // Create a child SMS and set the parent.
        $childSms = $this->createAnSms('Child SMS', 'Child SMS message');
        $childSms->setTranslationParent($parentSms);
        $this->em->flush();

        // Go to the edit page of the child SMS.
        $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$childSms->getId());
        $this->assertResponseIsSuccessful();

        // Remove the translation parent.
        $form = $this->client->getCrawler()->selectButton('Save')->form();

        $form['sms[translationParentSelector]'] = '';
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert that the translation parent has been removed.
        $this->em->clear();
        $updatedChildSms = $this->em->getRepository(Sms::class)->find($childSms->getId());
        $this->assertNull($updatedChildSms->getTranslationParent());
    }

    private function createAnSms(string $name, string $message): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage($message);
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }
}
