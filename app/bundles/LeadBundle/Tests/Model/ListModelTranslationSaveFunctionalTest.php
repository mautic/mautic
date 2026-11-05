<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListTranslationRepository;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ListModelTranslationSaveFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testCreatesSegmentAndTranslationsInOneSave(): void
    {
        $segment = $this->newSegment('Created');

        $this->getModel()->saveEntityWithTranslations($segment, [
            ['locale' => 'fr-CH', 'publicName' => 'Actualités Mautic'],
        ]);

        $this->assertNotNull($segment->getId());
        $this->assertSame(['fr_CH' => 'Actualités Mautic'], $this->getTranslations($segment));
    }

    public function testInvalidTranslationsDoNotSaveNewSegment(): void
    {
        $segment = $this->newSegment('Invalid');

        try {
            $this->getModel()->saveEntityWithTranslations($segment, [
                ['locale' => 'fr-CH', 'publicName' => 'Actualités'],
                ['locale' => 'fr_CH', 'publicName' => 'Nouvelles'],
            ]);
            self::fail('Duplicate locales must be rejected.');
        } catch (\InvalidArgumentException) {
            $this->assertNull($segment->getId());
            $this->assertSame(0, $this->em->getRepository(LeadList::class)->count(['alias' => 'translation-invalid']));
        }
    }

    public function testTurningOffPreferenceCenterPreservesTranslations(): void
    {
        $segment = $this->newSegment('Toggle');
        $model   = $this->getModel();
        $model->saveEntityWithTranslations($segment, [['locale' => 'fr', 'publicName' => 'Actualités']]);

        $segment->setIsPreferenceCenter(false);
        $model->saveEntityWithTranslations($segment, []);
        $this->assertSame(['fr' => 'Actualités'], $this->getTranslations($segment));

        $segment->setIsPreferenceCenter(true);
        $model->saveEntityWithTranslations($segment, [['locale' => 'fr', 'publicName' => 'Nouvelles']]);
        $this->assertSame(['fr' => 'Nouvelles'], $this->getTranslations($segment));
    }

    public function testPostSaveFailureRollsBackSegmentAndTranslations(): void
    {
        $segment    = $this->newSegment('Rollback');
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $listener = static function (): never {
            throw new \RuntimeException('Post-save failure');
        };
        $dispatcher->addListener(LeadEvents::LIST_POST_SAVE, $listener, 1000);

        try {
            $this->getModel()->saveEntityWithTranslations($segment, [['locale' => 'fr', 'publicName' => 'Actualités']]);
            self::fail('The post-save listener must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Post-save failure', $exception->getMessage());
        } finally {
            $dispatcher->removeListener(LeadEvents::LIST_POST_SAVE, $listener);
        }

        $table = $this->em->getClassMetadata(LeadList::class)->getTableName();
        $this->assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM '.$table.' WHERE alias = ?', ['translation-rollback']));
    }

    private function newSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias('translation-'.strtolower($name));
        $segment->setIsPreferenceCenter(true);

        return $segment;
    }

    private function getModel(): ListModel
    {
        $model = self::getContainer()->get(ListModel::class);
        $this->assertInstanceOf(ListModel::class, $model);

        return $model;
    }

    /**
     * @return array<string, string>
     */
    private function getTranslations(LeadList $segment): array
    {
        $repository = self::getContainer()->get(LeadListTranslationRepository::class);
        $this->assertInstanceOf(LeadListTranslationRepository::class, $repository);

        return $repository->findForSegmentIds([$segment->getId()])[$segment->getId()] ?? [];
    }
}
