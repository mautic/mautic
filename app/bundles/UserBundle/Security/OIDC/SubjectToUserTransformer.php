<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\Entity\SubjectId;
use Mautic\UserBundle\Security\OIDC\Repository\SubjectIdRepository;
use Symfony\Component\Form\DataTransformerInterface;

final class SubjectToUserTransformer implements DataTransformerInterface
{
    private SubjectIdRepository $subjectIdRepository;

    public function __construct(SubjectIdRepository $subjectIdRepository)
    {
        $this->subjectIdRepository = $subjectIdRepository;
    }

    public function transform($value): ?SubjectId
    {
        if ($value instanceof User) {
            return $this->subjectIdRepository->findOneBy(['user' => $value]) ?: (new SubjectId())->setUser($value);
        }

        throw new \InvalidArgumentException(\sprintf('Expected instance of %s. Given %s', User::class, \is_object($value) ? \get_class($value) : \gettype($value)));
    }

    public function reverseTransform($value): ?User
    {
        if ($value instanceof SubjectId) {
            return $value->getUser();
        }

        throw new \InvalidArgumentException(\sprintf('Expected instance of %s. Given %s', SubjectId::class, \is_object($value) ? \get_class($value) : \gettype($value)));
    }
}
