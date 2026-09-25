<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\LookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailPreviewOptionsType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'contact',
            LookupType::class,
            [
                'label' => 'mautic.email.preview.contact',
                'attr'  => [
                    'value'                => $options['contactName'],
                    'class'                => 'form-control',
                    'data-callback'        => 'activateContactPreviewLookupField',
                    'data-toggle'          => 'field-lookup',
                    'data-lookup-callback' => 'updateContactPreviewLookupListFilter',
                    'data-chosen-lookup'   => 'lead:contactList',
                    'placeholder'          => $this->translator->trans(
                        'mautic.email.preview.contact.placeholder'
                    ),
                    'data-no-record-message' => $this->translator->trans(
                        'mautic.core.form.nomatches'
                    ),
                ],
            ]
        );

        $builder->add(
            'company',
            LookupType::class,
            [
                'label' => 'mautic.email.preview.company',
                'attr'  => [
                    'value'                => $options['companyName'],
                    'class'                => 'form-control',
                    'data-callback'        => 'activateCompanyPreviewLookupField',
                    'data-toggle'          => 'field-lookup',
                    'data-lookup-callback' => 'updateCompanyPreviewLookupListFilter',
                    'data-chosen-lookup'   => 'lead:companyList',
                    'placeholder'          => $this->translator->trans(
                        'mautic.email.preview.company.placeholder'
                    ),
                    'data-no-record-message' => $this->translator->trans(
                        'mautic.core.form.nomatches'
                    ),
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['contactName', 'companyName']);
    }

    public function getBlockPrefix()
    {
        return 'email_preview_options';
    }
}
