<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Form\Transformer;

use Mautic\OpenIdBundle\Entity\SubjectId;
use Mautic\OpenIdBundle\Form\Transformer\SubjectToUserTransformer;
use Mautic\OpenIdBundle\Repository\SubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

final class SubjectToUserTransformerTest extends TestCase
{
    public function testTransformsUserWithSubjectId(): void
    {
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $user                = new User();
        $subjectId           = new SubjectId();

        $subjectId->setUser($user);
        $subjectIdRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($subjectId);

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);
        $subjectId                = $subjectToUserTransformer->transform($user);

        self::assertSame($user, $subjectId->getUser());
    }

    public function testTransformsUserWithoutSubjectId(): void
    {
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $user                = new User();

        $subjectIdRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);
        $subjectId                = $subjectToUserTransformer->transform($user);

        self::assertSame($user, $subjectId->getUser());
    }

    public function testTransformsUserWithWrongObjectType(): void
    {
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $subjectId           = new SubjectId();

        $subjectIdRepository->expects(self::never())
            ->method('findOneBy');

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(\sprintf('Expected instance of %s. Given %s', User::class, SubjectId::class));

        $subjectToUserTransformer->transform($subjectId);
    }

    public function testTransformsUserWithWrongType(): void
    {
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);

        $subjectIdRepository->expects(self::never())
            ->method('findOneBy');

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(\sprintf('Expected instance of %s. Given bool', User::class));

        $subjectToUserTransformer->transform(true);
    }

    public function testReverseTransformsSubjectId(): void
    {
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $user                = new User();
        $subjectId           = new SubjectId();

        $subjectId->setUser($user);

        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);
        $user                     = $subjectToUserTransformer->reverseTransform($subjectId);

        self::assertSame($user, $subjectId->getUser());
    }

    public function testReverseTransformsSubjectIdWithWrongObjectType(): void
    {
        $subjectIdRepository      = self::createMock(SubjectIdRepository::class);
        $user                     = new User();
        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(\sprintf('Expected instance of %s. Given %s', SubjectId::class, User::class));

        $subjectToUserTransformer->reverseTransform($user);
    }

    public function testReverseTransformsSubjectIdWithWrongType(): void
    {
        $subjectIdRepository      = self::createMock(SubjectIdRepository::class);
        $subjectToUserTransformer = new SubjectToUserTransformer($subjectIdRepository);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(\sprintf('Expected instance of %s. Given bool', SubjectId::class));

        $subjectToUserTransformer->reverseTransform(true);
    }
}
