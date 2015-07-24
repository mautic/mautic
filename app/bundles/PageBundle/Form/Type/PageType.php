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

        $variantParent = $options['data']->getVariantParent();
        $isVariant     = !empty($variantParent);

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
                    'class'                => 'form-control editor-fullpage editor-builder-tokens',
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
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.template.help',
                    'onchange' => 'Mautic.onBuilderModeSwitch(this);'
                ),
                'empty_value' => 'mautic.core.none'
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

        if ($isVariant) {
            $builder->add(
                'variantSettings',
                'pagevariant',
                array(
                    'label'       => false,
                    'page_entity' => $options['data'],
                    'data'        => $options['data']->getVariantSettings()
                )
            );
        } else {
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
            
            //Custom field for redirect type
            $redirectType = $options['data']->getRedirectType();
            $builder->add(
                'redirectType',
                'redirect_list',
                array(
                    'feature' => 'page',
                    'data' => $redirectType,
                    'attr' => array(
                        'class' => 'form-control',
                        'tooltip' => 'mautic.page.form.redirecttype.help',
                    ),
                    'empty_value' => 'mautic.page.form.redirecttype.none'
                )
            );
            
            //Custom field for redirect URL
            $model = $this->model;
            $model->getRepository()->setCurrentUser($this->user);
            $canViewOther = $this->canViewOther;
            
            $dataOptions = '';
            $pages = $model->getRepository()->getPageList('', 0, 0, $canViewOther, 'variant', array($options['data']->getId()));
            foreach ($pages as $page) {
                $dataOptions .= "|{$page['alias']}";
            }
            
            $redirectUrl = $options['data']->getRedirectUrl();
            $builder->add(
                'redirectUrl', 
                'url',
                array(
                      'required'    => true,
                    'label'       => 'mautic.page.form.redirecturl',
                    'label_attr'  => array(
                        'class' => 'control-label'
                    ),
                    'attr'        => array(
                        'class' => 'form-control', 
                        'maxlength' => 200, 
                        'tooltip' => 'mautic.page.form.redirecturl.help',
                        'data-toggle' => 'field-lookup',
                        'data-target' => 'redirectUrl',
                        'data-options' => $dataOptions
                    ),
                    'data'        => $redirectUrl
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
                    'required'   => false,
                    'disabled'   => $isVariant
                )
            );

            //add category
            $builder->add(
                'category',
                'category',
                array(
                    'bundle'   => 'page',
                    'disabled' => $isVariant
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
                    'disabled'   => $isVariant
                )
            );

            $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer($this->em, 'MauticPageBundle:Page', 'id');
            $builder->add(
                $builder->create(
                    'translationParent',
                    'page_list',
                    array(
                        'label'       => 'mautic.page.form.translationparent',
                        'label_attr'  => array('class' => 'control-label'),
                        'attr'        => array(
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.page.form.translationparent.help'
                        ),
                        'required'    => false,
                        'multiple'    => false,
                        'empty_value' => 'mautic.page.form.translationparent.empty',
                        'disabled'    => $isVariant,
                        'top_level'   => 'translation',
                        'ignore_ids'  => array((int) $options['data']->getId())
                    )
                )->addModelTransformer($transformer)
            );
        }

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
            'data_class' => 'Mautic\PageBundle\Entity\Page',
            'validation_groups' => array(
                'Mautic\PageBundle\Entity\Page',
                'determineValidationGroups'
            )
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
