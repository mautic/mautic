<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class EmailType extends AbstractType
{
    public const REPLY_TO_ADDRESS = 'replyToAddress';

    public function __construct(
        private UserHelper $userHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['body' => 'raw']));

        $builder->add(
            'subject',
            TextType::class,
            [
                'label'      => 'mautic.email.subject',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'empty_data' => '',
            ]
        );

        $user = $this->userHelper->getUser();

        $default = (empty($options['data']['fromname'])) ? $user->getFirstName().' '.$user->getLastName() : $options['data']['fromname'];
        $builder->add(
            'fromname',
            TextType::class,
            [
                'label'      => 'mautic.lead.email.from_name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'ri-user-6-fill',
                ],
                'required'   => false,
                'data'       => $default,
            ]
        );

        $default = (empty($options['data']['from'])) ? $user->getEmail() : $options['data']['from'];
        $builder->add(
            'from',
            TextType::class,
            [
                'label'       => 'mautic.lead.email.from_email',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'preaddon' => 'ri-mail-line',
                ],
                'required'    => false,
                'data'        => $default,
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.email.required',
                    ]),
                    new Email([
                        'message' => 'mautic.core.email.required',
                    ]),
                ],
            ]
        );

        $builder->add(
            self::REPLY_TO_ADDRESS,
            TextType::class,
            [
                'label'      => 'mautic.email.reply_to_email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'ri-mail-line',
                    'tooltip'  => 'mautic.email.reply_to_email.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'body',
            TextareaType::class,
            [
                'label'      => 'mautic.email.form.body',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'                => 'form-control editor editor-basic-fullpage editor-builder-tokens editor-email',
                    'data-token-callback'  => 'email:getBuilderTokens',
                    'data-token-activator' => '{',
                ],
            ]
        );

        $builder->add('list', HiddenType::class);

        $builder->add(
            'templates',
            EmailListType::class,
            [
                'label'      => 'mautic.lead.email.template',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.getLeadEmailContent(this)',
                ],
                'multiple' => false,
            ]
        );

        $builder->add('buttons', FormButtonsType::class, [
            'apply_text'  => false,
            'save_text'   => 'mautic.email.send',
            'save_class'  => 'btn btn-primary',
            'save_icon'   => 'ri-send-plane-line',
            'cancel_icon' => 'ri-close-line',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'lead_quickemail';
    }
}
