<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional\Entity;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;

final class SubjectIdTest extends MauticMysqlTestCase
{
    private string $prefix;
    private User $linkedUser;
    private User $unlinkedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prefix = self::getContainer()->getParameter('mautic.db_table_prefix');

        $role = new Role();
        $role->setName('Admin');
        $role->setIsAdmin(true);
        $this->em->persist($role);

        $this->linkedUser = new User();
        $this->linkedUser->setUsername('linked_admin');
        $this->linkedUser->setPassword('linked_admin');
        $this->linkedUser->setFirstName('linked_admin');
        $this->linkedUser->setLastName('linked_admin');
        $this->linkedUser->setEmail('linked_admin@mautic.local');
        $this->linkedUser->setRole($role);
        $this->em->persist($this->linkedUser);

        $this->unlinkedUser = new User();
        $this->unlinkedUser->setUsername('unlinked_admin');
        $this->unlinkedUser->setPassword('unlinked_admin');
        $this->unlinkedUser->setFirstName('unlinked_admin');
        $this->unlinkedUser->setLastName('unlinked_admin');
        $this->unlinkedUser->setEmail('unlinked_admin@mautic.local');
        $this->unlinkedUser->setRole($role);
        $this->em->persist($this->unlinkedUser);

        $subjectId = new OidcSubjectId($this->linkedUser, 'linked_admin');
        $this->em->persist($subjectId);

        $this->em->flush();
    }

    public function testInsertingNewSubjectId(): void
    {
        $subjectIdRepo         = $this->em->getRepository(OidcSubjectId::class);
        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'linked_admin']);
        $this->assertInstanceOf(OidcSubjectId::class, $subjectIdFromDatabase);

        $this->assertSame('linked_admin', $subjectIdFromDatabase->getSubjectID());
        $this->assertSame('linked_admin', $subjectIdFromDatabase->getUser()->getUsername());
    }

    public function testUpdateSubjectId(): void
    {
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        $subjectId = $subjectIdRepo->findOneBy(['subjectID' => 'linked_admin']);
        $this->assertInstanceOf(OidcSubjectId::class, $subjectId);
        $subjectId->setSubjectID('test2');
        $this->em->persist($subjectId);
        $this->em->flush();

        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'test2']);
        $this->assertInstanceOf(OidcSubjectId::class, $subjectIdFromDatabase);

        $this->assertEquals($subjectId, $subjectIdFromDatabase);
    }

    public function testDeleteSubjectId(): void
    {
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        $subjectId = $subjectIdRepo->findOneBy(['subjectID' => 'linked_admin']);
        $this->assertInstanceOf(OidcSubjectId::class, $subjectId);
        $this->em->remove($subjectId);
        $this->em->flush();

        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'test']);
        $this->assertNotInstanceOf(OidcSubjectId::class, $subjectIdFromDatabase);
    }

    public function testDeleteUserCascade(): void
    {
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        $this->em->remove($this->linkedUser);
        $this->em->flush();

        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'test']);
        $this->assertNotInstanceOf(OidcSubjectId::class, $subjectIdFromDatabase);
    }

    public function testSubjectUserIsUnique(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $this->connection->insert(
            $this->prefix.OidcSubjectId::TABLE_NAME,
            [
                'user_id'    => $this->linkedUser->getId(),
                'subject_id' => 'test2',
            ]
        );
    }

    public function testSubjectIdIsUnique(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $this->connection->insert(
            $this->prefix.OidcSubjectId::TABLE_NAME,
            [
                'user_id'    => $this->unlinkedUser->getId(),
                'subject_id' => 'linked_admin',
            ]
        );
    }
}
