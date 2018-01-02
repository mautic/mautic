<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\ToBcBccFieldsTrait;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SubmitActionEmailType.
 */
class SubmitActionEmailType extends AbstractType
{
    use FormFieldTrait;
    use ToBcBccFieldsTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * SubmitActionEmailType constructor.
     *
     * @param TranslatorInterface  $translator
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper)
    {
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = (isset($options['data']['subject']))
            ? $options['data']['subject']
            : $this->translator->trans(
                'mautic.form.action.sendemail.subject.default'
            );
        $builder->add(
            'subject',
          TextType::class,
            [
                'label'      => 'mautic.form.action.sendemail.subject',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'data'       => $data,
            ]
        );

        if (!isset($options['data']['message'])) {
            $fields  = $this->getFormFields($options['attr']['data-formid']);
            $message = '';

            foreach ($fields as $token => $label) {
                $message .= "<strong>$label</strong>: $token<br />";
            }
        } else {
            $message = $options['data']['message'];
        }

        $builder->add(
            'message',
          TextareaType::class,
            [
                'label'      => 'mautic.form.action.sendemail.message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor editor-basic'],
                'required'   => false,
                'data'       => $message,
            ]
        );

        if ($this->coreParametersHelper->getParameter('mailer_spool_type') == 'file') {
            $default = (isset($options['data']['immediately'])) ? $options['data']['immediately'] : false;
            $builder->add(
                'immediately',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.action.sendemail.immediately',
                    'data'  => $default,
                    'attr'  => [
                        'tooltip'    => 'mautic.form.action.sendemail.immediately.desc',
                    ],
                ]
            );
        } else {
            $builder->add(
                'immediately',
                HiddenType::class,
                [
                    'data'  => false,
                ]
            );
        }

        $default = (isset($options['data']['copy_lead'])) ? $options['data']['copy_lead'] : true;
        $builder->add(
            'copy_lead',
          YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.action.sendemail.copytolead',
                'data'  => $default,
            ]
        );

        $default = (isset($options['data']['email_to_owner'])) ? $options['data']['email_to_owner'] : false;
        $builder->add(
          'email_to_owner',
          YesNoButtonGroupType::class,
          [
            'label' => 'mautic.form.action.sendemail.emailtoowner',
            'data'  => $default,
          ]
        );

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

        $this->addToBcBccFields($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form_submitaction_sendemail';
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['formFields'] = $this->getFormFields($options['attr']['data-formid']);
    }
}
