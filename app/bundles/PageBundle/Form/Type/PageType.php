<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PageType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class PageType extends AbstractType
{

    private $translator;
    private $themes;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->themes     = $factory->getInstalledThemes('page');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('page.page', $options));

        $variantParent = $options['data']->getVariantParent();
        $isVariant     = !empty($variantParent);

        if ($isVariant) {

            $builder->add("pagevariant-panel-wrapper-start", 'panel_wrapper_start', array(
                'attr' => array(
                    'id' => "page-panel"
                )
            ));

            //details
            $builder->add("details-panel-start", 'panel_start', array(
                'label'      => 'mautic.page.page.panel.variantdetails',
                'dataParent' => '#page-panel',
                'bodyId'     => 'details-panel',
                'bodyAttr'   => array('class' => 'in')
            ));
        }

        $builder->add('title', 'text', array(
            'label'      => 'mautic.page.page.form.title',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        if (!$isVariant) {
            $builder->add('alias', 'text', array(
                'label'      => 'mautic.page.page.form.alias',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.page.help.alias',
                ),
                'required'   => false
            ));
        }

        if (!$isVariant) {
            $builder->add('category_lookup', 'text', array(
                'label'      => 'mautic.page.page.form.category',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'tooltip'     => 'mautic.core.help.autocomplete',
                    'placeholder' => $this->translator->trans('mautic.core.form.uncategorized')
                ),
                'mapped'     => false,
                'required'   => false
            ));

            $builder->add('category', 'hidden_entity', array(
                'required'       => false,
                'repository'     => 'MauticPageBundle:Category',
                'error_bubbling' => false,
                'read_only'      => ($isVariant) ? true : false
            ));
        }

        //build a list
        $builder->add('template', 'choice', array(
            'choices'       => $this->themes,
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.page.page.form.template',
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.form.template.help'
            )
        ));

        if (!$isVariant) {
            $builder->add('language', 'locale', array(
                'label'      => 'mautic.page.page.form.language',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.page.form.language.help',
                ),
                'required'   => false
            ));

            $builder->add('translationParent_lookup', 'text', array(
                'label'      => 'mautic.page.page.form.translationparent',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.page.form.translationparent.help'
                ),
                'mapped'     => false,
                'required'   => false
            ));

            $builder->add('translationParent', 'hidden_entity', array(
                'required'       => false,
                'repository'     => 'MauticPageBundle:Page',
                'error_bubbling' => false
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

        if (!$isVariant) {
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
        }

        $builder->add('metaDescription', 'textarea', array(
            'label'      => 'mautic.page.page.form.metadescription',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'maxlength' => 160),
            'required'   => false
        ));

        $builder->add('sessionId', 'hidden');

        if ($isVariant) {

            $builder->add("details-panel-end", 'panel_end');

            $builder->add("abtest-panel-start", 'panel_start', array(
                'label' => 'mautic.page.page.panel.abtest',
                'dataParent' => '#page-panel',
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

            $builder->add('variant_settings', 'pagevariant', array(
                'label'       => false,
                'page_entity' => $options['data']
            ));

            $builder->add("abtest-panel-end", 'panel_end');

            $builder->add("pagevariant-panel-wrapper-end", 'panel_wrapper_end');
        }

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'builder',
                    'label' => 'mautic.page.page.launch.builder',
                    'attr'  => array(
                        'class'   => 'btn btn-warning',
                        'icon'    => 'fa fa-file-text-o padding-sm-right',
                        'onclick' => "Mautic.launchPageEditor();"
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
            'data_class' => 'Mautic\PageBundle\Entity\Page'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "page";
    }
}