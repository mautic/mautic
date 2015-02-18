<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

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
    private $defaultTheme;
    private $em;
    private $request;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->translator   = $factory->getTranslator();
        $this->themes       = $factory->getInstalledThemes('email');
        $this->defaultTheme = $factory->getParameter('theme');
        $this->em           = $factory->getEntityManager();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html', 'customHtml' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('email.email', $options));

        $variantParent = $options['data']->getVariantParent();
        $isVariant     = !empty($variantParent);

        $builder->add('subject', 'text', array(
            'label'      => 'mautic.email.subject',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $template = $options['data']->getTemplate();
        if (empty($template)) {
            $template = $this->defaultTheme;
        }
        $builder->add('template', 'theme_list', array(
            'feature' => 'email',
            'data'    => $template,
            'attr'    => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.form.template.help'
            )
        ));

        $builder->add('isPublished', 'yesno_button_group');

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

        $builder->add('plainText', 'textarea', array(
            'label'      => 'mautic.email.form.plaintext',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'tooltip' => 'mautic.email.form.plaintext.help',
                'class'   => 'form-control',
                'rows'    => '15'
            ),
            'required'   => false
        ));

        $contentMode = $options['data']->getContentMode();
        if (empty($contentMode)) {
            $contentMode = 'custom';
        }
        $builder->add('contentMode', 'button_group', array(
            'choice_list' => new ChoiceList(
                array('custom', 'builder'),
                array('mautic.email.form.contentmode.custom', 'mautic.email.form.contentmode.builder')
            ),
            'expanded'    => true,
            'multiple'    => false,
            'label'       => 'mautic.email.form.contentmode',
            'empty_value' => false,
            'required'    => false,
            'data'        => $contentMode,
            'attr'        => array(
                'onChange' => 'Mautic.toggleEmailContentMode(this);'
            )
        ));

        $builder->add('customHtml', 'textarea', array(
            'label'      => 'mautic.email.form.customhtml',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'tooltip' => 'mautic.email.form.customhtml.help',
                'class'   => 'form-control editor-fullpage'
            ),
            'required'   => false
        ));

        if ($isVariant) {
            $builder->add('variantSettings', 'emailvariant', array(
                'label' => false
            ));
        } else {
            $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer($this->em, 'MauticFormBundle:Form', 'id');
            $builder->add(
                $builder->create('unsubscribeForm', 'form_list', array(
                    'label'       => 'mautic.email.form.unsubscribeform',
                    'label_attr'  => array('class' => 'control-label'),
                    'attr'        => array(
                        'class'            => 'form-control',
                        'tootlip'          => 'mautic.email.form.unsubscribeform.tooltip',
                        'data-placeholder' => $this->translator->trans('mautic.core.form.chooseone')
                    ),
                    'required'    => false,
                    'multiple'    => false,
                    'empty_value' => '',
                ))
                    ->addModelTransformer($transformer)
            );

            //add category
            $builder->add('category', 'category', array(
                'bundle' => 'email'
            ));

            //add lead lists
            $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer($this->em, 'MauticLeadBundle:LeadList', 'id', true);
            $builder->add(
                $builder->create('lists', 'leadlist_choices', array(
                    'label'      => 'mautic.email.form.list',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'multiple'   => true,
                    'expanded'   => false,
                    'required'   => false
                ))
                    ->addModelTransformer($transformer)
            );


            $builder->add('language', 'locale', array(
                'label'      => 'mautic.core.language',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control'
                ),
                'required'   => false,
            ));
        }

        $builder->add('sessionId', 'hidden');

        if (!empty($options['update_select'])) {
            $builder->add('buttons', 'form_buttons', array(
                'apply_text' => false
            ));
            $builder->add('updateSelect', 'hidden', array(
               'data'   => $options['update_select'],
               'mapped' => false
            ));
        } else {
            $builder->add('buttons', 'form_buttons');
        }


        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\EmailBundle\Entity\Email'
        ));

        $resolver->setOptional(array('update_select'));
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return "emailform";
    }
}
