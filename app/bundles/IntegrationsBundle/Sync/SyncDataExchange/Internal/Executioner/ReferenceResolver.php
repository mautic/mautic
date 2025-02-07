<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\Exception\ReferenceNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

final class ReferenceResolver implements ReferenceResolverInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param ObjectChangeDAO[] $changedObjects
     */
    public function resolveReferences(string $objectName, array $changedObjects): void
    {
        if (Contact::NAME !== $objectName) {
            DebugLogger::log(
                'N/A',
                sprintf(
                    'references are currently resolved only for %s. Given %s',
                    Contact::NAME,
                    $objectName
                ),
                __CLASS__.':'.__FUNCTION__
            );

            return;
        }

        foreach ($changedObjects as $changedObject) {
            foreach ($changedObject->getFields() as $field) {
                $value           = $field->getValue();
                $normalizedValue = $value->getNormalizedValue();

                if (!$normalizedValue instanceof ReferenceValueDAO) {
                    continue;
                }

                try {
                    $resolvedReference = $this->resolveReference($normalizedValue);
                } catch (ReferenceNotFoundException) {
                    $resolvedReference = null;
                }

                $resolvedValue = new NormalizedValueDAO($value->getType(), $resolvedReference, $resolvedReference);
                $changedObject->addField(new FieldDAO($field->getName(), $resolvedValue));
            }
        }
    }

    /**
     * @throws ReferenceNotFoundException
     */
    private function resolveReference(ReferenceValueDAO $value): ?string
    {
        if (MauticSyncDataExchange::OBJECT_COMPANY === $value->getType() && 0 < $value->getValue()) {
            return $this->getCompanyNameById($value->getValue());
        }

        return null;
    }

    /**
     * @throws ReferenceNotFoundException
     */
    private function getCompanyNameById(int $id): string
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('c.companyname');
        $qb->from(MAUTIC_TABLE_PREFIX.'companies', 'c');
        $qb->where('c.id = :id');
        $qb->setParameter('id', $id);

        $name = $qb->executeQuery()->fetchOne();

        if (false === $name) {
            throw new ReferenceNotFoundException(sprintf('Company reference for ID "%d" not found', $id));
        }

        return $name;
    }
}
