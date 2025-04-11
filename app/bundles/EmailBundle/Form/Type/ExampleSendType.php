<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class ExampleSendType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private CorePermissions $security, private UserHelper $userHelper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'emails',
            SortableListType::class,
            [
                'entry_type'       => EmailType::class,
                'label'            => 'mautic.email.example_recipients',
                'add_value_button' => 'mautic.email.add_recipient',
                'option_notblank'  => false,
            ]
        );

        if ($this->security->isAdmin()
            || $this->security->hasEntityAccess(
                'lead:leads:viewown',
                'lead:leads:viewother',
                $this->userHelper->getUser()->getId()
            )) {
            $builder->add(
                'contact',
                LookupType::class,
                [
                    'attr' => [
                        'class'                => 'form-control',
                        'data-callback'        => 'activateExampleContactLookupField',
                        'data-toggle'          => 'field-lookup',
                        'data-lookup-callback' => 'updateExampleContactLookupListFilter',
                        'data-chosen-lookup'   => 'lead:contactList',
                        'placeholder'          => $this->translator->trans(
                            'mautic.lead.list.form.startTyping'
                        ),
                        'data-no-record-message' => $this->translator->trans(
                            'mautic.core.form.nomatches'
                        ),
                    ],
                ]
            );

            $builder->add(
                'contact_id',
                HiddenType::class
            );
        }

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text' => false,
                'save_text'  => 'mautic.email.send',
                'save_icon'  => 'ri-send-plane-line',
            ]
        );
    }
}
