<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Security\OIDC\OidcSubjectIdType;
use Mautic\UserBundle\Security\OIDC\SubjectToUserTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class UserTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly Settings $parameters, private readonly OidcSubjectIdRepository $subjectIdRepository)
    {
    }

    /**
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        return [UserType::class];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->parameters->isEnabled() || !empty($options['in_profile'])) {
            return;
        }

        $builder->add($builder->create(
            'subjectID',
            OidcSubjectIdType::class,
            [
                'mapped' => false,
                'data'   => $options['data'],
            ])
            ->addModelTransformer(new SubjectToUserTransformer($this->subjectIdRepository)));
    }
}
