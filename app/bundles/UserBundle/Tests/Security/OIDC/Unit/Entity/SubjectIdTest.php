<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

final class SubjectIdTest extends TestCase
{
    public function testLoadMetadata(): void
    {
        $metadata = new ClassMetadata(OidcSubjectId::class);
        OidcSubjectId::loadMetadata($metadata);

        $this->assertSame(OidcSubjectId::TABLE_NAME, $metadata->getTableName());
        $this->assertSame(OidcSubjectIdRepository::class, $metadata->customRepositoryClassName);
        $this->assertSame('user', $metadata->getAssociationMapping('user')['fieldName']);
        $this->assertSame('subjectID', $metadata->getFieldMapping('subjectID')['fieldName']);
    }

    public function testDefaultValues(): void
    {
        $subjectId = new OidcSubjectId();

        $this->assertNull($subjectId->getSubjectID());
        $this->expectException(\Error::class);
        $subjectId->getUser();
    }

    public function testSettersGetters(): void
    {
        $subjectId = new OidcSubjectId();
        $user      = new User();

        $subjectId->setUser($user);
        $subjectId->setSubjectID('subjectID');

        $this->assertSame($user, $subjectId->getUser());
        $this->assertSame('subjectID', $subjectId->getSubjectID());
    }
}
