<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\UserBundle\Entity\OidcSubjectId;

/**
 * Applies MySQL-only utf8mb4_bin collation to OidcSubjectId.subjectID
 * after the connection/platform is available.
 *
 * PostgreSQL default string comparison is already case-sensitive, so no option is set.
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
final class OidcSubjectIdCollationListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();

        if (OidcSubjectId::class !== $metadata->getName()) {
            return;
        }

        if (!isset($metadata->fieldMappings['subjectID'])) {
            return;
        }

        $platform = $args->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();

        if (DatabasePlatform::isPostgreSQL($platform)) {
            // Ensure no MySQL-only option leaks into PostgreSQL schema generation
            unset($metadata->fieldMappings['subjectID']['options']['collation']);

            return;
        }

        // MySQL / MariaDB – force binary (case-sensitive) collation
        $metadata->fieldMappings['subjectID']['options']['collation'] = 'utf8mb4_bin';
    }
}
