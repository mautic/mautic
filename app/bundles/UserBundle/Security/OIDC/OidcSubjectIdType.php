<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Exception\OidcIdTakenException;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OidcSubjectIdType extends AbstractType
{
    private LinkerInterface $linker;
    private TranslatorInterface $translator;

    public function __construct(LinkerInterface $linker, TranslatorInterface $translator)
    {
        $this->linker      = $linker;
        $this->translator  = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('subjectID', TextType::class, [
            'label'      => 'mautic.open_id.form.subject_id',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.open_id.form.subject_id.tooltip',
            ],
            'required'    => false,
            'constraints' => [
                new Assert\Type(['type' => 'string', 'groups' => ['subjectID']]),
                new Assert\Length(['max' => 255, 'groups' => ['subjectID']]),
            ],
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            if (!$event->getForm()->isValid()) {
                return;
            }

            $subjectId = $event->getData();
            \assert($subjectId instanceof OidcSubjectId);
            try {
                $this->linker->editLinkToUser($subjectId, $subjectId->getUser());
            } catch (OidcIdTakenException $e) {
                $error = new FormError($this->translator->trans($e->getMessage()));
                $event->getForm()->get('subjectID')->addError($error);
            }
        }, -10);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'        => OidcSubjectId::class,
                'validation_groups' => [OidcSubjectId::class, 'subjectID'],
            ]
        );
    }

    public function getName(): string
    {
        return 'user_openid';
    }
}
