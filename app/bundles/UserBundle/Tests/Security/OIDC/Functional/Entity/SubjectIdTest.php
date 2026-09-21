<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional\Entity;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\Entity\SubjectId;
use Mautic\UserBundle\Tests\Security\OIDC\Functional\LoadFixturesTrait;

final class SubjectIdTest extends MauticMysqlTestCase
{
    use LoadFixturesTrait;

    public function testInsertingNewSubjectId(): void
    {
        $subjectIdRepo         = $this->em->getRepository(OidcSubjectId::class);
        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'linked_admin']);
        \assert($subjectIdFromDatabase instanceof SubjectId);

        self::assertEquals('linked_admin', $subjectIdFromDatabase->getSubjectID());
        self::assertEquals('linked_admin', $subjectIdFromDatabase->getUser()->getUsername());
    }

    public function testUpdateSubjectId(): void
    {
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        $subjectId = $subjectIdRepo->findOneBy(['subjectID' => 'linked_admin']);
        \assert($subjectId instanceof SubjectId);
        $subjectId->setSubjectID('test2');
        $this->em->persist($subjectId);
        $this->em->flush();

        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'test2']);
        \assert($subjectIdFromDatabase instanceof SubjectId);

        $this->assertEquals($subjectId, $subjectIdFromDatabase);
    }

    public function testDeleteSubjectId(): void
    {
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        $subjectId = $subjectIdRepo->findOneBy(['subjectID' => 'linked_admin']);
        \assert($subjectId instanceof SubjectId);
        $this->em->remove($subjectId);
        $this->em->flush();

        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'test']);
        $this->assertNull($subjectIdFromDatabase);
    }

    public function testDeleteUserCascade(): void
    {
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);
        $userRepo      = $this->em->getRepository(User::class);

        $user = $userRepo->findOneBy(['username' => 'linked_admin']);
        \assert($user instanceof User);
        $this->em->remove($user);
        $this->em->flush();

        $subjectIdFromDatabase = $subjectIdRepo->findOneBy(['subjectID' => 'test']);
        $this->assertNull($subjectIdFromDatabase);
    }

    public function testSubjectUserIsUnique(): void
    {
        $userRepo  = $this->em->getRepository(User::class);

        $user = $userRepo->findOneBy(['username' => 'linked_admin']);
        \assert($user instanceof User);

        $subjectId = new SubjectId();
        $subjectId->setUser($user);
        $subjectId->setSubjectID('test2');

        self::expectException(UniqueConstraintViolationException::class);
        $this->em->persist($subjectId);
        $this->em->flush();
    }

    public function testSubjectIdIsUnique(): void
    {
        $userRepo  = $this->em->getRepository(User::class);

        $user = $userRepo->findOneBy(['username' => 'unlinked_admin']);
        \assert($user instanceof User);

        $subjectId = new SubjectId();
        $subjectId->setUser($user);
        $subjectId->setSubjectID('linked_admin');

        self::expectException(UniqueConstraintViolationException::class);
        $this->em->persist($subjectId);
        $this->em->flush();
    }
}
