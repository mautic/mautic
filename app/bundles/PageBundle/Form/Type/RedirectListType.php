<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RedirectListType.
 */
class RedirectListType extends AbstractType
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices'     => $this->coreParametersHelper->getParameter('redirect_list_types'),
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

        $resolver->setDefined(['feature']);
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
