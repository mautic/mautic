<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\OpenIdBundle\Entity\SubjectId;
use Mautic\OpenIdBundle\Repository\SubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

final class SubjectIdTest extends TestCase
{
    public function testLoadMetadata(): void
    {
        $metadata = new ClassMetadata(SubjectId::class);
        SubjectId::loadMetadata($metadata);

        self::assertSame(SubjectId::TABLE_NAME, $metadata->getTableName());
        self::assertSame(SubjectIdRepository::class, $metadata->customRepositoryClassName);
        self::assertSame('user', $metadata->getAssociationMapping('user')['fieldName']);
        self::assertSame('subjectID', $metadata->getFieldMapping('subjectID')['fieldName']);
    }

    public function testDefaultValues(): void
    {
        $subjectId = new SubjectId();

        self::assertNull($subjectId->getSubjectID());
        self::expectError();
        self::assertNull($subjectId->getUser());
    }

    public function testSettersGetters(): void
    {
        $subjectId = new SubjectId();
        $user      = new User();

        $subjectId->setUser($user);
        $subjectId->setSubjectID('subjectID');

        self::assertSame($user, $subjectId->getUser());
        self::assertSame('subjectID', $subjectId->getSubjectID());
    }
}
