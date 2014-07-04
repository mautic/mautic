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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PageType
 *
 * @package Mautic\PageType\Form\Type
 */
class PageType extends AbstractType
{

    private $translator;
    private $templateDir;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, $kernelDir) {
        $this->translator  = $factory->getTranslator();
        $this->templateDir = $kernelDir . '/Resources/views/Templates';
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber($this->translator->trans(
            'mautic.core.form.inform'
        )));

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
            'required'   => false
        ));

        //build a list
        $finder    = new Finder();
        $finder->directories()->in($this->templateDir);
        $templates = array();
        foreach ($finder as $dir) {
            $template = $dir->getRelativePathname();

            //get the config file
           $tmplConfig = include_once $this->templateDir . '/' . $template . '/config.php';

            if (isset($tmplConfig['slots']['page'])) {
                //read the config file and get the name
                $templates[$template] = $tmplConfig['name'];
            }
        }

        $builder->add('category_lookup', 'text', array(
            'label'      => 'mautic.page.page.form.category',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.core.help.autocomplete',
                'placeholder' => $this->translator->trans('mautic.core.form.uncategorized')
            ),
            'mapped'     => false,
            'required'   => false
        ));

        $builder->add('category', 'hidden_entity', array(
            'required'   => false,
            'repository' => 'MauticPageBundle:Category',
            'error_bubbling' => false
        ));

        $builder->add('template', 'choice', array(
            'choices'       => $templates,
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

        $builder->add('language', 'locale', array(
            'label'      => 'mautic.page.page.form.language',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.form.language.help',
            ),
            'required'   => false
        ));

        $builder->add('parent_lookup', 'text', array(
            'label'      => 'mautic.page.page.form.parent',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.page.form.parent.help'
            ),
            'mapped'     => false,
            'required'   => false
        ));

        $builder->add('parent', 'hidden_entity', array(
            'required'   => false,
            'repository' => 'MauticPageBundle:Page',
            'error_bubbling' => false
        ));

        $builder->add('isPublished', 'choice', array(
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
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'  => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'  => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('metaDescription', 'textarea', array(
            'label'      => 'mautic.page.page.form.metadescription',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'maxlength' => 160),
            'required'   => false
        ));

        $builder->add('sessionId', 'hidden');

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