<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldEmailType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $doNotSubmitEmails  = $this->coreParametersHelper->get('do_not_submit_emails');
        $hasDenyList        = !empty($doNotSubmitEmails) && is_array($doNotSubmitEmails);
        $defaultDonotSubmit = $options['data']['donotsubmit'] ?? ($hasDenyList ? true : false);

        $builder->add(
            'donotsubmit',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.field.type.donotsubmit',
                'data'  => $defaultDonotSubmit,
                'attr'  => [
                    'tooltip' => 'mautic.form.field.help.donotsubmit',
                ],
            ]
        );

        $builder->add(
            'donotsubmit_validationmsg',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.validationmsg',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"formfield_validation_donotsubmit_1": "checked"}',
                ],
                'data'     => $options['data']['donotsubmit_validationmsg'] ?? $this->translator->trans('mautic.form.submission.email.donotsubmit.invalid', [], 'validators'),
                'required' => false,
            ]
        );
    }
}
