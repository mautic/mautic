<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Security\OIDC\DTO\Settings;
use Mautic\UserBundle\Security\OIDC\Form\Transformer\SubjectToUserTransformer;
use Mautic\UserBundle\Security\OIDC\Form\Type\SubjectIdType;
use Mautic\UserBundle\Security\OIDC\Repository\SubjectIdRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class UserTypeExtension extends AbstractTypeExtension
{
    private Settings $parameters;
    private SubjectIdRepository $subjectIdRepository;

    public function __construct(Settings $parameters, SubjectIdRepository $subjectIdRepository)
    {
        $this->parameters          = $parameters;
        $this->subjectIdRepository = $subjectIdRepository;
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
            SubjectIdType::class,
            [
                'mapped' => false,
                'data'   => $options['data'],
            ])
            ->addModelTransformer(new SubjectToUserTransformer($this->subjectIdRepository)));
    }
}
