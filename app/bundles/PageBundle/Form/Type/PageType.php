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
 */
class PageType extends AbstractType
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var array
     */
    private $themes;

    /**
     * @var bool|mixed
     */
    private $defaultTheme;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator   = $factory->getTranslator();
        $this->themes       = $factory->getInstalledThemes('page');
        $this->defaultTheme = $factory->getParameter('theme');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('page.page', $options));

        $variantParent = $options['data']->getVariantParent();
        $isVariant     = !empty($variantParent);

        $builder->add('title', 'text', array(
            'label'      => 'mautic.page.page.form.title',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('alias', 'text', array(
            'label'      => 'mautic.page.page.form.alias',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.help.alias',
            ),
            'required'   => false,
            'disabled'   => $isVariant
        ));

        //add category
        $builder->add('category', 'category', array(
            'bundle'   => 'page',
            'disabled' => $isVariant
        ));

        //build a list
        $template = $options['data']->getTemplate();
        if (empty($template)) {
            $template = $this->defaultTheme;
        }
        $builder->add('template', 'choice', array(
            'choices'       => $this->themes,
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.page.page.form.template',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.form.template.help'
            ),
            'data'          => $template
        ));

        $builder->add('language', 'locale', array(
            'label'      => 'mautic.page.page.form.language',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.form.language.help',
            ),
            'required'   => false,
            'disabled'   => $isVariant
        ));

        $builder->add('translationParent_lookup', 'text', array(
            'label'      => 'mautic.page.page.form.translationparent',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.form.translationparent.help'
            ),
            'mapped'     => false,
            'required'   => false,
            'disabled'   => $isVariant
        ));

        $builder->add('translationParent', 'hidden_entity', array(
            'required'       => false,
            'repository'     => 'MauticPageBundle:Page',
            'error_bubbling' => false,
            'disabled'   => $isVariant
        ));

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

        $builder->add('metaDescription', 'textarea', array(
            'label'      => 'mautic.page.page.form.metadescription',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'maxlength' => 160),
            'required'   => false
        ));

        $builder->add('sessionId', 'hidden');

        if ($isVariant) {
            $builder->add('variantSettings', 'pagevariant', array(
                'label'       => false,
                'page_entity' => $options['data']
            ));
        }

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'builder',
                    'label' => 'mautic.page.page.launch.builder',
                    'attr'  => array(
                        'class'   => 'btn btn-default',
                        'icon'    => 'fa fa-cube text-mautic',
                        'onclick' => 'Mautic.launchPageEditor();'
                    )
                )
            )
        ));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\PageBundle\Entity\Page'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'page';
    }
}
