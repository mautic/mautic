<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\DataTransformerInterface;

final class SubjectToUserTransformer implements DataTransformerInterface
{
    private OidcSubjectIdRepository $subjectIdRepository;

    public function __construct(OidcSubjectIdRepository $subjectIdRepository)
    {
        $this->subjectIdRepository = $subjectIdRepository;
    }

    public function transform($value): ?OidcSubjectId
    {
        if ($value instanceof User) {
            return $this->subjectIdRepository->findOneBy(['user' => $value]) ?: (new OidcSubjectId())->setUser($value);
        }

        throw new \InvalidArgumentException(\sprintf('Expected instance of %s. Given %s', User::class, \is_object($value) ? \get_class($value) : \gettype($value)));
    }

    public function reverseTransform($value): ?User
    {
        if ($value instanceof OidcSubjectId) {
            return $value->getUser();
        }

        throw new \InvalidArgumentException(\sprintf('Expected instance of %s. Given %s', SubjectId::class, \is_object($value) ? \get_class($value) : \gettype($value)));
    }
}
