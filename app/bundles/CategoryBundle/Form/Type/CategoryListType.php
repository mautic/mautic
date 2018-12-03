<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CategoryListType.
 */
class CategoryListType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CategoryModel
     */
    private $model;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Router
     */
    private $router;

    /**
     * CategoryListType constructor.
     *
     * @param EntityManager       $em
     * @param TranslatorInterface $translator
     * @param CategoryModel       $model
     * @param Router              $router
     */
    public function __construct(EntityManager $em, TranslatorInterface $translator, CategoryModel $model, Router $router)
    {
        $this->em         = $em;
        $this->translator = $translator;
        $this->model      = $model;
        $this->router     = $router;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (true === $options['return_entity']) {
            $transformer = new IdToEntityModelTransformer($this->em, 'MauticCategoryBundle:Category', 'id');
            $builder->addModelTransformer($transformer);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $createNew = $this->translator->trans('mautic.category.createnew');
                $categories = $this->model->getLookupResults($options['bundle'], '', 0);
                $choices = [];
                foreach ($categories as $l) {
                    $choices[$l['id']] = $l['title'];
                }
                $choices['new'] = $createNew;

                return $choices;
            },
            'label'       => 'mautic.core.category',
            'label_attr'  => ['class' => 'control-label'],
            'multiple'    => false,
            'empty_value' => 'mautic.core.form.uncategorized',
            'attr'        => function (Options $options) {
                $modalHeader = $this->translator->trans('mautic.category.header.new');
                $newUrl = $this->router->generate('mautic_category_action', [
                    'objectAction' => 'new',
                    'bundle'       => $options['bundle'],
                    'inForm'       => 1,
                ]);

                return [
                    'class'    => 'form-control category-select',
                    'onchange' => "Mautic.loadAjaxModalBySelectValue(this, 'new', '{$newUrl}', '{$modalHeader}');",
                ];
            },
            'required'      => false,
            'return_entity' => true,
        ]);

        $resolver->setRequired(['bundle']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'category';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
