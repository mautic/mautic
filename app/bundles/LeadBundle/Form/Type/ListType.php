<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependency;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ListType extends AbstractType
{
    private $translator;
    private $stageChoices        = [];
    private $assetChoices        = [];
    private $categoriesChoices   = [];

    /**
     * @var ListModel
     */
    private $listModel;

    public function __construct(TranslatorInterface $translator, ListModel $listModel, StageModel $stageModel, CategoryModel $categoryModel)
    {
        $this->translator = $translator;
        $this->listModel  = $listModel;

        $stages = $stageModel->getRepository()->getSimpleList();
        foreach ($stages as $stage) {
            $this->stageChoices[$stage['label']] = $stage['value'];
        }

        $categories = $categoryModel->getLookupResults('global', null, 0);

        foreach ($categories as $category) {
            $this->categoriesChoices[$category['title']] = $category['id'];
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.list', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'publicName',
            TextType::class,
            [
                'label'      => 'mautic.lead.list.form.publicname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.list.form.publicname.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.lead.list.help.alias',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'segment',
            ]
        );

        $builder->add(
            'isGlobal',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.lead.list.form.isglobal',
                'attr'       => [
                    'tooltip' => 'mautic.lead.list.form.isglobal.tooltip',
                ],
            ]
        );

        $builder->add(
            'isPreferenceCenter',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.lead.list.form.isPreferenceCenter',
                'attr'       => [
                    'tooltip' => 'mautic.lead.list.form.isPreferenceCenter.tooltip',
                ],
            ]
        );

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $filterModalTransformer = new FieldFilterTransformer($this->translator, ['object'=>'lead']);
        $builder->add(
            $builder->create(
                'filters',
                CollectionType::class,
                [
                    'entry_type'    => FilterType::class,
                    'entry_options' => [
                        'label'          => false,
                        'stage'          => $this->stageChoices,
                        'globalcategory' => $this->categoriesChoices,
                    ],
                    'error_bubbling' => false,
                    'mapped'         => true,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'label'          => false,
                    'constraints'    => [
                        new CircularDependency([
                            'message' => 'mautic.core.segment.circular_dependency_exists',
                        ]),
                    ],
                ]
            )->addModelTransformer($filterModalTransformer)
        );

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => LeadList::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields']         = $this->listModel->getChoiceFields();
        $view->vars['stage']          = $this->stageChoices;
        $view->vars['globalcategory'] = $this->categoriesChoices;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist';
    }
}
