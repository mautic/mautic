<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class RedirectListType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class RedirectListType extends AbstractType {
    
    private $factory;
    
    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory = $factory;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $factory = $this->factory;
        $resolver->setDefaults(array(
            'choices'       => $factory->getParameter('redirect_list_types'),
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.page.form.redirecttype',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'          => array(
                'class' => 'form-control'
            ),
            'feature'       => 'all'
        ));

        $resolver->setOptional(array('feature'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "redirect_list";
    }
    
    /**
     * @return string
     */
    public function getParent() {
        return "choice";
    }
    
}
