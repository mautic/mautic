<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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
     * @var bool|mixed
     */
    private $defaultTheme;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Mautic\PageBundle\Model\PageModel
     */
    private $model;

    /**
     * @var \Mautic\UserBundle\Model\UserModel
     */
    private $user;

    /**
     * @var bool
     */
    private $canViewOther = false;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator   = $factory->getTranslator();
        $this->em           = $factory->getEntityManager();
        $this->model        = $factory->getModel('page');
        $this->canViewOther = $factory->getSecurity()->isGranted('page:pages:viewother');
        $this->user         = $factory->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html', 'customHtml' => 'html', 'redirectUrl' => 'url')));
        $builder->addEventSubscriber(new FormExitSubscriber('page.page', $options));

        $builder->add(
            'title',
            'text',
            array(
                'label'      => 'mautic.core.title',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

        $builder->add(
            'customHtml',
            'textarea',
            array(
                'label'      => 'mautic.page.form.customhtml',
                'required'   => false,
                'attr'       => array(
                    'class'                => 'form-control editor editor-basic-fullpage editor-builder-tokens builder-html',
                    'data-token-callback'  => 'page:getBuilderTokens',
                    'data-token-activator' => '{'
                )
            )
        );

        $template = $options['data']->getTemplate();
        if (empty($template)) {
            $template = $this->defaultTheme;
        }
        $builder->add(
            'template',
            'theme_list',
            array(
                'feature' => 'page',
                'data'    => $template,
                'attr'    => array(
                    'class'   => 'form-control not-chosen hidden',
                    'tooltip' => 'mautic.page.form.template.help',
                ),
                'empty_value' => 'mautic.core.none',
                'data' => $options['data']->getTemplate() ? $options['data']->getTemplate() : 'blank'
            )
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'publishUp',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            )
        );

        $builder->add(
            'publishDown',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            )
        );

        $builder->add('sessionId', 'hidden');

        //Custom field for redirect URL
        $this->model->getRepository()->setCurrentUser($this->user);

        $redirectUrlDataOptions = '';
        $pages                  = $this->model->getRepository()->getPageList('', 0, 0, $this->canViewOther, 'variant', array($options['data']->getId()));
        foreach ($pages as $page) {
            $redirectUrlDataOptions .= "|{$page['alias']}";
        }

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticPageBundle:Page');
        $builder->add(
            $builder->create(
                'variantParent',
                'hidden'
            )->addModelTransformer($transformer)
        );

        $builder->add(
            $builder->create(
                'translationParent',
                'page_list',
                array(
                    'label'       => 'mautic.core.form.translation_parent',
                    'label_attr'  => array('class' => 'control-label'),
                    'attr'        => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.form.translation_parent.help'
                    ),
                    'required'    => false,
                    'multiple'    => false,
                    'empty_value' => 'mautic.core.form.translation_parent.empty',
                    'top_level'   => 'translation',
                    'ignore_ids'  => array((int) $options['data']->getId())
                )
            )->addModelTransformer($transformer)
        );

        $formModifier = function(FormInterface $form, $isVariant) {
            if ($isVariant) {
                $form->add(
                    'variantSettings',
                    'pagevariant',
                    array(
                        'label' => false
                    )
                );
            }
        };

        // Building the form
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier(
                    $event->getForm(),
                    $event->getData()->getVariantParent()
                );
            }
        );

        // After submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier(
                    $event->getForm(),
                    $data['variantParent']
                );
            }
        );

        $builder->add(
            'metaDescription',
            'textarea',
            array(
                'label'      => 'mautic.page.form.metadescription',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control', 'maxlength' => 160),
                'required'   => false
            )
        );

        $builder->add(
          'redirectType',
          'redirect_list',
          array(
              'feature'     => 'page',
              'attr'        => array(
                  'class'   => 'form-control',
                  'tooltip' => 'mautic.page.form.redirecttype.help',
              ),
              'empty_value' => 'mautic.page.form.redirecttype.none'
          )
        );

        $builder->add(
            'redirectUrl',
            'url',
            array(
                'required'   => true,
                'label'      => 'mautic.page.form.redirecturl',
                'label_attr' => array(
                    'class' => 'control-label'
                ),
                'attr'       => array(
                    'class'        => 'form-control',
                    'maxlength'    => 200,
                    'tooltip'      => 'mautic.page.form.redirecturl.help',
                    'data-toggle'  => 'field-lookup',
                    'data-target'  => 'redirectUrl',
                    'data-options' => $redirectUrlDataOptions
                ),
            )
        );

        $builder->add(
            'alias',
            'text',
            array(
                'label'      => 'mautic.core.alias',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.help.alias',
                ),
                'required'   => false
            )
        );

        //add category
        $builder->add(
            'category',
            'category',
            array(
                'bundle'   => 'page'
            )
        );

        $builder->add(
            'language',
            'locale',
            array(
                'label'      => 'mautic.core.language',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.language.help',
                ),
                'required'   => false,
                'empty_data' => 'en'
            )
        );

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'builder',
                    'label' => 'mautic.core.builder',
                    'attr'  => array(
                        'class'   => 'btn btn-default btn-dnd btn-nospin btn-builder text-primary',
                        'icon'    => 'fa fa-cube',
                        'onclick' => "Mautic.launchBuilder('page');"
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
