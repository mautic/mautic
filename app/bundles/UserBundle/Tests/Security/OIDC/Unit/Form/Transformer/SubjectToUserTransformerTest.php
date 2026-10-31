<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Transformer;

use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\SubjectToUserTransformer;
use PHPUnit\Framework\TestCase;

final class SubjectToUserTransformerTest extends TestCase
{
    public function testTransformsUserWithSubjectId(): void
    {
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $user                = new User();
        $subjectId           = new OidcSubjectId($user);

        $subjectIdRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($subjectId);

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);
        $subjectId                = $subjectToUserTransformer->transform($user);

        $this->assertSame($user, $subjectId->getUser());
    }

    public function testTransformsUserWithoutSubjectId(): void
    {
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $user                = new User();

        $subjectIdRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);
        $subjectId                = $subjectToUserTransformer->transform($user);

        $this->assertSame($user, $subjectId->getUser());
    }

    public function testTransformsUserWithWrongObjectType(): void
    {
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $subjectId           = new OidcSubjectId(new User());

        $subjectIdRepository->expects($this->never())
            ->method('findOneBy');

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Expected instance of %s. Given %s', User::class, OidcSubjectId::class));

        $subjectToUserTransformer->transform($subjectId);
    }

    public function testTransformsUserWithWrongType(): void
    {
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);

        $subjectIdRepository->expects($this->never())
            ->method('findOneBy');

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Expected instance of %s. Given bool', User::class));

        $subjectToUserTransformer->transform(true);
    }

    public function testReverseTransformsSubjectId(): void
    {
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $user                = new User();
        $subjectId           = new OidcSubjectId($user);

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);
        $user                     = $subjectToUserTransformer->reverseTransform($subjectId);

        $this->assertSame($user, $subjectId->getUser());
    }

    public function testReverseTransformsSubjectIdWithWrongObjectType(): void
    {
        $subjectIdRepository      = $this->createStub(OidcSubjectIdRepository::class);
        $user                     = new User();
        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Expected instance of %s. Given %s', OidcSubjectId::class, User::class));

        $subjectToUserTransformer->reverseTransform($user);
    }

    public function testReverseTransformsSubjectIdWithWrongType(): void
    {
        $subjectIdRepository      = $this->createStub(OidcSubjectIdRepository::class);
        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Expected instance of %s. Given bool', OidcSubjectId::class));

        $subjectToUserTransformer->reverseTransform(true);
    }
}
