<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
 * Class CategoryListType
 *
 * @package Mautic\CategoryBundle\Form\Type
 */
class CategoryListType extends AbstractType
{

    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory = $factory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new IdToEntityModelTransformer($this->factory->getEntityManager(), 'MauticCategoryBundle:Category', 'id');
        $builder->addModelTransformer($transformer);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $model = $this->factory->getModel('category');

        $resolver->setDefaults(array(
            'choices'    =>  function (Options $options) use ($model) {
                $categories = $model->getLookupResults($options['bundle'], '', 0);
                $choices = array();
                foreach ($categories as $l) {
                    $choices[$l['id']] = $l['title'];
                }

                return $choices;
            },
            'label'      => 'mautic.category.form.category',
            'label_attr' => array('class' => 'control-label'),
            'multiple'   => false,
            'empty_value'=> 'mautic.core.form.uncategorized',
            'attr'       => array(
                'class'       => 'form-control chosen',
            ),
            'required'   => false
        ));

        $resolver->setRequired(array('bundle'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "category";
    }

    public function getParent()
    {
        return "choice";
    }
}
