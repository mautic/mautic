<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EmailImportExportSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
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

    /**
     * The export writes snake_case keys while the entity exposes camelCase properties. When the
     * import hydrated straight through the serializer those keys were silently discarded, so an
     * imported email arrived with no body at all and fell back to the blank theme.
     */
    public function testImportKeepsTheFieldsTheExportWrote(): void
    {
        $email = new Email();
        $email->setName('Round Trip Email');
        $email->setSubject('Round Trip Subject');
        $email->setCustomHtml('<html><body><p>Original body</p></body></html>');
        $email->setPlainText('Original body');
        $email->setPreheaderText('Original preheader');
        $email->setEmailType('transactional');
        $email->setLanguage('en');
        $email->setIsPublished(true);
        $email->setPublishUp(new \DateTime('2026-01-01 09:00:00'));
        $email->setPublishDown(new \DateTime('2026-12-31 18:00:00'));

        $this->em->persist($email);
        $this->em->flush();

        $exportEvent = new EntityExportEvent(Email::ENTITY_NAME, $email->getId());
        $this->dispatcher->dispatch($exportEvent);

        $exported = reset($exportEvent->getEntities()[Email::ENTITY_NAME]);
        $this->assertIsArray($exported);

        // A fresh uuid makes the import create a second email rather than update the source one.
        $exported['uuid'] = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

        $importEvent = new EntityImportEvent(Email::ENTITY_NAME, [$exported], 1);
        $this->dispatcher->dispatch($importEvent);

        $this->em->clear();

        $imported = $this->em->getRepository(Email::class)->findOneBy(['uuid' => $exported['uuid']]);
        $this->assertInstanceOf(Email::class, $imported);

        $this->assertSame($email->getCustomHtml(), $imported->getCustomHtml());
        $this->assertSame($email->getPlainText(), $imported->getPlainText());
        $this->assertSame($email->getPreheaderText(), $imported->getPreheaderText());
        $this->assertSame($email->getEmailType(), $imported->getEmailType());
        $this->assertSame($email->getLanguage(), $imported->getLanguage());

        // The export serialises these as DATE_ATOM strings, so the import has to turn them back
        // into dates rather than storing null and silently republishing the email.
        $this->assertInstanceOf(\DateTimeInterface::class, $imported->getPublishUp());
        $this->assertInstanceOf(\DateTimeInterface::class, $imported->getPublishDown());
        $this->assertSame(
            $email->getPublishUp()->format('Y-m-d H:i'),
            $imported->getPublishUp()->format('Y-m-d H:i')
        );
    }
}
