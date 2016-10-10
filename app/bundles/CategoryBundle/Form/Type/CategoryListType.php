<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CategoryListType.
 */
class CategoryListType extends AbstractType
{
    private $em;

    private $model;

    private $translator;

    private $router;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->em         = $factory->getEntityManager();
        $this->translator = $factory->getTranslator();
        $this->model      = $factory->getModel('category');
        $this->router     = $factory->getRouter();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new IdToEntityModelTransformer($this->em, 'MauticCategoryBundle:Category', 'id');
        $builder->addModelTransformer($transformer);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $model       = $this->model;
        $createNew   = $this->translator->trans('mautic.category.createnew');
        $modalHeader = $this->translator->trans('mautic.category.header.new');
        $router      = $this->router;
        $resolver->setDefaults([
            'choices' => function (Options $options) use ($model, $createNew, $modalHeader) {
                $categories = $model->getLookupResults($options['bundle'], '', 0);
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
            'attr'        => function (Options $options) use ($modalHeader, $router) {
                $newUrl = $router->generate('mautic_category_action', [
                    'objectAction' => 'new',
                    'bundle'       => $options['bundle'],
                    'inForm'       => 1,
                ]);

                return [
                    'class'    => 'form-control category-select',
                    'onchange' => "Mautic.loadAjaxModalBySelectValue(this, 'new', '{$newUrl}', '{$modalHeader}');",
                ];
            },
            'required' => false,
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

    public function getParent()
    {
        return 'choice';
    }
}
