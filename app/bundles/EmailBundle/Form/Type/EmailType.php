<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CategoryBundle\Helper\FormHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EmailType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailType extends AbstractType
{

    private $translator;
    private $themes;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->themes     = $factory->getInstalledThemes('email');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('email.email', $options));

        $isVariant     = $options['data']->isVariant();

        if ($isVariant) {
            $builder->add("emailvariant-panel-wrapper-start", 'panel_wrapper_start', array(
                'attr' => array(
                    'id' => "email-panel"
                )
            ));

            //details
            $builder->add("details-panel-start", 'panel_start', array(
                'label'      => 'mautic.email.panel.variantdetails',
                'dataParent' => '#email-panel',
                'bodyId'     => 'details-panel',
                'bodyAttr'   => array('class' => 'in')
            ));
        }

        $builder->add('subject', 'text', array(
            'label'      => 'mautic.email.form.subject',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        if (!$isVariant) {
            //add category
            FormHelper::buildForm($this->translator, $builder);

            $builder->add('lists', 'leadlist_choices', array(
                'label'      => 'mautic.email.form.list',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control'
                ),
                'multiple' => true,
                'expanded' => false
            ));
        }

        //build a list
        $builder->add('template', 'choice', array(
            'choices'       => $this->themes,
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.email.form.template',
            'empty_value'   => false,
            'required'      => false,
            'label_attr'    => array('class' => 'control-label'),
            'attr'          => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.form.template.help'
            )
        ));

        if (!$isVariant) {
            $builder->add('language', 'locale', array(
                'label'      => 'mautic.email.form.language',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control'
                ),
                'required'   => false
            ));
        }

        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.core.form.ispublished',
            'empty_value'   => false,
            'required'      => false
        ));

        //todo - add drag-n-drop tokens for plain text version
        $builder->add('plainText', 'textarea', array(
            'label'      => 'mautic.email.form.plaintext',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'tooltip' => 'mautic.email.form.plaintext.help',
                'class'   => 'form-control'
            )
        ));

        if ($isVariant) {
            $builder->add("details-panel-end", 'panel_end');

            $builder->add("abtest-panel-start", 'panel_start', array(
                'label' => 'mautic.email.panel.abtest',
                'dataParent' => '#email-panel',
                'bodyId'     => 'abtest-panel'
            ));

            $builder->add('publishUp', 'datetime', array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            ));

            $builder->add('publishDown', 'datetime', array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            ));

            $builder->add('variant_settings', 'emailvariant', array(
                'label'       => false
            ));

            $builder->add("abtest-panel-end", 'panel_end');

            $builder->add("emailvariant-panel-wrapper-end", 'panel_wrapper_end');
        }

        $builder->add('sessionId', 'hidden');

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'builder',
                    'label' => 'mautic.email.launch.builder',
                    'attr'  => array(
                        'class'   => 'btn btn-default',
                        'icon'    => 'fa fa-file-text-o padding-sm-right text-info',
                        'onclick' => "Mautic.launchEmailEditor();"
                    )
                )
            )
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\EmailBundle\Entity\Email'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "emailform";
    }
}