<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class RedirectListType.
 */
class RedirectListType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $factory = $this->factory;
        $resolver->setDefaults([
            'choices'     => $factory->getParameter('redirect_list_types'),
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.page.form.redirecttype',
            'label_attr'  => ['class' => 'control-label'],
            'empty_value' => false,
            'required'    => false,
            'attr'        => [
                'class' => 'form-control',
            ],
            'feature' => 'all',
        ]);

        $resolver->setOptional(['feature']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'redirect_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
