<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\UserBundle\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class UserInviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'email',
            EmailType::class,
            [
                'label'      => 'mautic.user.invite.email.label',
                'label_attr' => ['class' => 'sr-only'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.user.invite.email.label',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.user.invite.error.email_required',
                    ]),
                    new Email([
                        'message' => 'mautic.user.invite.error.email_invalid',
                    ]),
                ],
            ]
        );

        $builder->add(
            'role',
            EntityType::class,
            [
                'label'      => 'mautic.user.invite.role.label',
                'label_attr' => ['class' => 'sr-only'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'placeholder'   => 'mautic.user.invite.role.placeholder',
                'class'         => Role::class,
                'choice_label'  => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('r')
                    ->where('r.isPublished = true')
                    ->orderBy('r.name', 'ASC'),
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.user.invite.error.role_required',
                    ]),
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
