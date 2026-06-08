<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\SmsBundle\Entity\Sms;
use Symfony\Component\HttpFoundation\Request;

final class SmsControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateEntitiesTrait;

    public function testSmsCanBeCreatedWithTranslationParent(): void
    {
        // Arrange
        $parentSms = $this->createAndPersistSms('Parent SMS', 'Parent SMS message');

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/new');
        $this->assertResponseIsSuccessful();

        $form                                   = $crawler->selectButton('Save')->form();
        $form['sms[name]']                      = 'Child SMS';
        $form['sms[message]']                   = 'Child SMS message';
        $form['sms[translationParentSelector]'] = (string) $parentSms->getId();

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert
        $childSms = $this->em->getRepository(Sms::class)->findOneBy(['name' => 'Child SMS']);
        $this->assertInstanceOf(Sms::class, $childSms);
        $this->assertInstanceOf(Sms::class, $childSms->getTranslationParent());
        $this->assertSame($parentSms->getId(), $childSms->getTranslationParent()->getId());
    }

    public function testSmsCannotBeItsOwnTranslationParent(): void
    {
        // Arrange
        $sms = $this->createAndPersistSms('Test SMS', 'Test SMS message');

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$sms->getId());
        $this->assertResponseIsSuccessful();

        // Assert
        $options = $crawler->filter('#sms_translationParentSelector option');
        $this->assertCount(2, $options);
        $this->assertSame('Choose a translated item...', $options->eq(0)->text());
        $this->assertSame('Create new...', $options->eq(1)->text());

        // Ensure the SMS itself is not in the dropdown
        $optionValues = $options->each(fn ($node) => $node->attr('value'));
        $this->assertNotContains((string) $sms->getId(), $optionValues);
    }

    public function testSmsWithTranslationParentCanBeEdited(): void
    {
        // Arrange
        $parentSms    = $this->createAndPersistSms('Parent SMS', 'Parent SMS message');
        $childSms     = $this->createAndPersistSms('Child SMS', 'Child SMS message');
        $childSms->setTranslationParent($parentSms);

        $newParentSms = $this->createAndPersistSms('New Parent SMS', 'New Parent SMS message');
        $this->em->flush();

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$childSms->getId());
        $this->assertResponseIsSuccessful();

        // Assert original parent is selected
        $this->assertSame(
            (string) $parentSms->getId(),
            $crawler->filter('#sms_translationParentSelector option[selected]')->attr('value')
        );

        // Change parent
        $form                                   = $crawler->selectButton('Save')->form();
        $form['sms[translationParentSelector]'] = (string) $newParentSms->getId();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert parent updated
        $this->em->refresh($childSms);
        $this->assertInstanceOf(Sms::class, $childSms->getTranslationParent());
        $this->assertSame($newParentSms->getId(), $childSms->getTranslationParent()->getId());
    }

    public function testTranslationParentCanBeRemovedFromSms(): void
    {
        // Arrange
        $parentSms = $this->createAndPersistSms('Parent SMS', 'Parent SMS message');
        $childSms  = $this->createAndPersistSms('Child SMS', 'Child SMS message');
        $childSms->setTranslationParent($parentSms);
        $this->em->flush();

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$childSms->getId());
        $this->assertResponseIsSuccessful();

        $form                                   = $crawler->selectButton('Save')->form();
        $form['sms[translationParentSelector]'] = '';
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert
        $this->em->refresh($childSms);
        $this->assertNull($childSms->getTranslationParent());
    }

    public function testTranslationsAreDisplayedOnViewPage(): void
    {
        // Arrange
        $parentSms = $this->createAndPersistSms('Parent SMS', 'Parent SMS message', 'en');
        $childSms  = $this->createAndPersistSms('Child SMS', 'Child SMS message', 'fr');
        $childSms->setTranslationParent($parentSms);
        $parentSms->addTranslationChild($childSms);

        $this->em->flush();

        // Act & Assert - Parent view
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$parentSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->client->click($crawler->selectLink('Translations')->link());
        $this->assertSelectorTextContains('#translation-container', 'Child SMS');

        // Act & Assert - Child view
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$childSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->client->click($crawler->selectLink('Translations')->link());
        $this->assertSelectorTextContains('#translation-container', 'Parent SMS');
    }

    private function createAndPersistSms(string $name, string $message, string $locale = 'en'): Sms
    {
        $sms = $this->createAnSms($name, $message, true, $locale);
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }
}
