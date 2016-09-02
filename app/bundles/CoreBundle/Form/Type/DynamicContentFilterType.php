<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class DynamicContentFilterType extends AbstractType
{
    private $operatorChoices;
    private $translator;
    private $currentListId;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel       = $factory->getModel('lead.list');
        $operatorChoices = $listModel->getFilterExpressionFunctions();

        $this->operatorChoices = array();
        foreach ($operatorChoices as $key => $value) {
            if (empty($value['hide'])) {
                $this->operatorChoices[$key] = $value['label'];
            }
        }

        $this->translator    = $factory->getTranslator();
        $this->currentListId = $factory->getRequest()->attributes->get('objectId', false);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'tokenName',
            'text',
            [
                'label' => 'mautic.core.dynamicContent.token_name',
                'attr'  => [
                    'class' => 'form-control'
                ]
            ]
        );

        $builder->add(
            'content',
            'textarea',
            [
                'label' => 'mautic.core.dynamicContent.default_content',
                'attr'  => [
                    'class' => 'form-control'
                ]
            ]
        );

        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type' => 'dynamic_content_filter_entry',
                    'options' => [
                        'label' => false,
                        'attr'  => [
                            'class' => 'form-control'
                        ],
                        'content' => '',
                        'filters' => ''
                    ],
                    'allow_add' => true
                ]
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            [
                'timezones',
                'countries',
                'regions',
                'fields',
                'lists',
                'emails',
                'tags',
                'stage'
            ]
        );

        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields'] = $options['fields'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "dynamic_content_filter";
    }
}