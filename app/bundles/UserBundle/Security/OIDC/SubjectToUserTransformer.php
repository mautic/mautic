<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\DataTransformerInterface;

final readonly class SubjectToUserTransformer implements DataTransformerInterface
{
    public function __construct(private OidcSubjectIdRepository $subjectIdRepository)
    {
    }

    public function transform($value): OidcSubjectId
    {
        if ($value instanceof User) {
            return $this->subjectIdRepository->findOneBy(['user' => $value]) ?: new OidcSubjectId($value);
        }

        throw new \InvalidArgumentException(\sprintf('Expected instance of %s. Given %s', User::class, get_debug_type($value)));
    }

    public function reverseTransform($value): User
    {
        if ($value instanceof OidcSubjectId) {
            return $value->getUser();
        }

        throw new \InvalidArgumentException(\sprintf('Expected instance of %s. Given %s', OidcSubjectId::class, get_debug_type($value)));
    }
}
