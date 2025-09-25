<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\NotificationBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\Request;

final class MobileNotificationTranslationFunctionalTest extends MauticMysqlTestCase
{
    public function testNotificationCanBeCreatedWithTranslationParent(): void
    {
        // Arrange
        $parentNotification = $this->createAndPersistNotification('Parent Notification', 'Parent Notification message');

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/new');
        $this->assertResponseIsSuccessful();

        $form                                                   = $crawler->selectButton('Save')->form();
        $form['mobile_notification[name]']                      = 'Child Notification';
        $form['mobile_notification[message]']                   = 'Child Notification message';
        $form['mobile_notification[heading]']                   = 'Child Notification';
        $form['mobile_notification[translationParentSelector]'] = (string) $parentNotification->getId();

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert
        $childNotification = $this->em->getRepository(Notification::class)->findOneBy(['name' => 'Child Notification']);
        $this->assertInstanceOf(Notification::class, $childNotification);
        $this->assertInstanceOf(Notification::class, $childNotification->getTranslationParent());
        $this->assertSame($parentNotification->getId(), $childNotification->getTranslationParent()->getId());
    }

    public function testNotificationCannotBeItsOwnTranslationParent(): void
    {
        // Arrange
        $notification = $this->createAndPersistNotification('Test Notification', 'Test Notification message');

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/edit/'.$notification->getId());
        $this->assertResponseIsSuccessful();

        // Assert
        $options = $crawler->filter('#mobile_notification_translationParentSelector option');
        $this->assertCount(2, $options);
        $this->assertSame('Choose a translated item...', $options->eq(0)->text());
        $this->assertSame('Create new...', $options->eq(1)->text());

        // Ensure the Notification itself is not in the dropdown
        $optionValues = $options->each(fn ($node) => $node->attr('value'));
        $this->assertNotContains((string) $notification->getId(), $optionValues);
    }

    public function testNotificationWithTranslationParentCanBeEdited(): void
    {
        // Arrange
        $parentNotification    = $this->createAndPersistNotification('Parent Notification', 'Parent Notification message');
        $childNotification     = $this->createAndPersistNotification('Child Notification', 'Child Notification message');
        $childNotification->setTranslationParent($parentNotification);

        $newParentNotification = $this->createAndPersistNotification('New Parent Notification', 'New Parent Notification message');
        $this->em->flush();

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/edit/'.$childNotification->getId());
        $this->assertResponseIsSuccessful();

        // Assert original parent is selected
        $this->assertSame(
            (string) $parentNotification->getId(),
            $crawler->filter('#mobile_notification_translationParentSelector option[selected]')->attr('value')
        );

        // Change parent
        $form                                                   = $crawler->selectButton('Save')->form();
        $form['mobile_notification[translationParentSelector]'] = (string) $newParentNotification->getId();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert parent updated
        $this->em->refresh($childNotification);
        $this->assertInstanceOf(Notification::class, $childNotification->getTranslationParent());
        $this->assertSame($newParentNotification->getId(), $childNotification->getTranslationParent()->getId());
    }

    public function testTranslationParentCanBeRemovedFromNotification(): void
    {
        // Arrange
        $parentNotification = $this->createAndPersistNotification('Parent Notification', 'Parent Notification message');
        $childNotification  = $this->createAndPersistNotification('Child Notification', 'Child Notification message');
        $childNotification->setTranslationParent($parentNotification);
        $this->em->flush();

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/edit/'.$childNotification->getId());
        $this->assertResponseIsSuccessful();

        $form                                                   = $crawler->selectButton('Save')->form();
        $form['mobile_notification[translationParentSelector]'] = '';
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert
        $this->em->refresh($childNotification);
        $this->assertNull($childNotification->getTranslationParent());
    }

    public function testTranslationsAreDisplayedOnViewPage(): void
    {
        // Arrange
        $parentNotification = $this->createAndPersistNotification('Parent Notification', 'Parent Notification message', 'en');
        $childNotification  = $this->createAndPersistNotification('Child Notification', 'Child Notification message', 'fr');
        $childNotification->setTranslationParent($parentNotification);
        $parentNotification->addTranslationChild($childNotification);

        $this->em->flush();

        // Act & Assert - Parent view
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/view/'.$parentNotification->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->client->click($crawler->selectLink('Translations')->link());
        $this->assertSelectorTextContains('#translation-container', 'Child Notification');

        // Act & Assert - Child view
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/view/'.$childNotification->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->client->click($crawler->selectLink('Translations')->link());
        $this->assertSelectorTextContains('#translation-container', 'Parent Notification');
    }

    private function createANotification(string $name, string $message, bool $isPublished = true, string $locale = 'en'): Notification
    {
        $notification = new Notification();
        $notification->setName($name);
        $notification->setMessage($message);
        $notification->setHeading($name);
        $notification->setLanguage($locale);
        $notification->setIsPublished($isPublished);
        $notification->setMobile(true);

        return $notification;
    }

    private function createAndPersistNotification(string $name, string $message, string $locale = 'en'): Notification
    {
        $notification = $this->createANotification($name, $message, true, $locale);
        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }
}
