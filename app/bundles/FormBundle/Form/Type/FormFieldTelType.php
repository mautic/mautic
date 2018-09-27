<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FormFieldTextType.
 */
class FormFieldTelType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FormFieldTelType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'international',
            'yesno_button_group',
            [
                'label' => 'mautic.form.field.type.tel.international',
            ]
        );

        $builder->add(
            'international_validationmsg',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.validationmsg',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => $this->translator->trans('mautic.core.form.default').': '.$this->translator->trans('mautic.form.submission.phone.invalid', [], 'validators'),
                    'data-show-on' => '{"formfield_properties_international_1": "checked"}',
                ],
            ]
        );
    }
}
