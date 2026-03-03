<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EmailImportExportSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = static::getContainer()->get('event_dispatcher');
    }

    public function testExportEmailWithNullParentReferences(): void
    {
        $email = new Email();
        $email->setName('Test Email Without Parents');
        $email->setSubject('Test Subject');
        $email->setIsPublished(true);

        $this->em->persist($email);
        $this->em->flush();

        $event = new EntityExportEvent(Email::ENTITY_NAME, $email->getId());
        $this->dispatcher->dispatch($event);

        $entities = $event->getEntities();

        $this->assertArrayHasKey(Email::ENTITY_NAME, $entities);
        $this->assertCount(1, $entities[Email::ENTITY_NAME]);

        $exportedEmail = reset($entities[Email::ENTITY_NAME]);
        $this->assertIsArray($exportedEmail);
        $this->assertNull($exportedEmail['translation_parent_id']);
        $this->assertNull($exportedEmail['variant_parent_id']);
        $this->assertSame($email->getId(), $exportedEmail['id']);
    }

    public function testExportEmailWithTranslationParentExportsIdNotEntity(): void
    {
        $parentEmail = new Email();
        $parentEmail->setName('Parent Email');
        $parentEmail->setSubject('Parent Subject');
        $parentEmail->setIsPublished(true);
        $parentEmail->setLanguage('en');

        $this->em->persist($parentEmail);
        $this->em->flush();

        $childEmail = new Email();
        $childEmail->setName('Child Translation Email');
        $childEmail->setSubject('Child Subject');
        $childEmail->setIsPublished(true);
        $childEmail->setLanguage('de');
        $childEmail->setTranslationParent($parentEmail);
        $parentEmail->addTranslationChild($childEmail);

        $this->em->persist($childEmail);
        $this->em->flush();

        $event = new EntityExportEvent(Email::ENTITY_NAME, $childEmail->getId());
        $this->dispatcher->dispatch($event);

        $entities = $event->getEntities();

        $this->assertArrayHasKey(Email::ENTITY_NAME, $entities);
        $this->assertCount(1, $entities[Email::ENTITY_NAME]);

        $exportedEmail = reset($entities[Email::ENTITY_NAME]);
        $this->assertIsArray($exportedEmail);
        $this->assertIsInt($exportedEmail['translation_parent_id']);
        $this->assertSame($parentEmail->getId(), $exportedEmail['translation_parent_id']);
        $this->assertNull($exportedEmail['variant_parent_id']);
    }

    public function testExportEmailWithVariantParentExportsIdNotEntity(): void
    {
        $parentEmail = new Email();
        $parentEmail->setName('Parent Email');
        $parentEmail->setSubject('Parent Subject');
        $parentEmail->setIsPublished(true);

        $this->em->persist($parentEmail);
        $this->em->flush();

        $variantEmail = new Email();
        $variantEmail->setName('Variant Email');
        $variantEmail->setSubject('Variant Subject');
        $variantEmail->setIsPublished(true);
        $variantEmail->setVariantParent($parentEmail);
        $variantEmail->setVariantSettings(['weight' => 50]);
        $parentEmail->addVariantChild($variantEmail);

        $this->em->persist($variantEmail);
        $this->em->flush();

        $event = new EntityExportEvent(Email::ENTITY_NAME, $variantEmail->getId());
        $this->dispatcher->dispatch($event);

        $entities = $event->getEntities();

        $this->assertArrayHasKey(Email::ENTITY_NAME, $entities);
        $this->assertCount(1, $entities[Email::ENTITY_NAME]);

        $exportedEmail = reset($entities[Email::ENTITY_NAME]);
        $this->assertIsArray($exportedEmail);
        $this->assertIsInt($exportedEmail['variant_parent_id']);
        $this->assertSame($parentEmail->getId(), $exportedEmail['variant_parent_id']);
        $this->assertNull($exportedEmail['translation_parent_id']);
    }

    public function testExportEmailWithNullVariantSettingsReturnsEmptyArray(): void
    {
        $parentEmail = new Email();
        $parentEmail->setName('Parent Email');
        $parentEmail->setSubject('Parent Subject');
        $parentEmail->setIsPublished(true);

        $this->em->persist($parentEmail);
        $this->em->flush();

        $variantEmail = new Email();
        $variantEmail->setName('Variant Email With Null Settings');
        $variantEmail->setSubject('Variant Subject');
        $variantEmail->setIsPublished(true);
        $variantEmail->setVariantParent($parentEmail);
        $parentEmail->addVariantChild($variantEmail);

        $this->em->persist($variantEmail);
        $this->em->flush();

        // Force variant_settings to null in database to simulate legacy data
        $this->connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'emails SET variant_settings = NULL WHERE id = ?',
            [$variantEmail->getId()]
        );

        $this->em->clear();

        $event = new EntityExportEvent(Email::ENTITY_NAME, $variantEmail->getId());
        $this->dispatcher->dispatch($event);

        $entities = $event->getEntities();

        $this->assertArrayHasKey(Email::ENTITY_NAME, $entities);
        $this->assertCount(1, $entities[Email::ENTITY_NAME]);

        $exportedEmail = reset($entities[Email::ENTITY_NAME]);
        $this->assertIsArray($exportedEmail);
        $this->assertIsArray($exportedEmail['variant_settings']);
        $this->assertSame([], $exportedEmail['variant_settings']);
    }
}
