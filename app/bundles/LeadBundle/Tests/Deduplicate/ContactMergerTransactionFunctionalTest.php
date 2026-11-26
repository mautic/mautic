<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Deduplicate;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ContactMergerTransactionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testFailedMergeRollsBackInheritedStageAndRelatedChanges(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get(CompanyModel::class);

        $stage = new Stage();
        $stage->setName('Rollback test stage');

        $winner = new Lead();
        $winner->setEmail('rollback-winner@example.com');

        $loser = new Lead();
        $loser->setEmail('rollback-loser@example.com');
        $leadModel->addToStage($loser, $stage, 'Test setup');

        $this->em->persist($stage);
        $this->em->persist($winner);
        $this->em->persist($loser);
        $this->em->flush();

        $company = new Company();
        $company->setName('Rollback test company');
        $companyModel->saveEntity($company);
        $companyModel->addLeadToCompany($company, $loser);

        $winnerId = $winner->getId();
        $loserId  = $loser->getId();
        $connection = $this->em->getConnection();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $failingListener = static function (): never {
            throw new \RuntimeException('Deliberate post-merge failure');
        };
        $dispatcher->addListener(LeadEvents::LEAD_POST_MERGE, $failingListener, 1024);

        /** @var ContactMerger $merger */
        $merger = self::getContainer()->get(ContactMerger::class);

        try {
            try {
                $merger->merge($winner, $loser);
                self::fail('The deliberate post-merge exception was not thrown.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Deliberate post-merge failure', $exception->getMessage());
            }

            $this->assertNull($connection->fetchOne(
                'SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'leads WHERE id = ?',
                [$winnerId]
            ));
            $this->assertSame(0, (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE lead_id = ?',
                [$winnerId]
            ));
            $this->assertSame(1, (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'leads WHERE id = ?',
                [$loserId]
            ));
            $this->assertSame(0, (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'companies_leads WHERE lead_id = ?',
                [$winnerId]
            ));
            $this->assertSame(0, (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'contact_merge_records WHERE merged_id = ?',
                [$loserId]
            ));
        } finally {
            $dispatcher->removeListener(LeadEvents::LEAD_POST_MERGE, $failingListener);

            /** @var ManagerRegistry $managerRegistry */
            $managerRegistry = self::getContainer()->get(ManagerRegistry::class);
            $managerRegistry->resetManager();
            $entityManager = $managerRegistry->getManager();
            $this->assertInstanceOf(EntityManager::class, $entityManager);
            $this->em         = $entityManager;
            $this->connection = $this->em->getConnection();
        }
    }
}
