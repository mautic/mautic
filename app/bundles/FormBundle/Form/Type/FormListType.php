<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PointActionFormSubmitType.
 */
class FormListType extends AbstractType
{
    private $viewOther;
    private $repo;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->viewOther = $factory->getSecurity()->isGranted('form:forms:viewother');
        $this->repo      = $factory->getModel('form')->getRepository();

        $this->repo->setCurrentUser($factory->getUser());
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $viewOther = $this->viewOther;
        $repo      = $this->repo;

        $resolver->setDefaults([
            'choices' => function (Options $options) use ($repo, $viewOther) {
                static $choices;

                if (is_array($choices)) {
                    return $choices;
                }

                $choices = [];

                $forms = $repo->getFormList('', 0, 0, $viewOther, $options['form_type']);
                foreach ($forms as $form) {
                    $choices[$form['id']] = $form['name'];
                }

                //sort by language
                asort($choices);

                return $choices;
            },
            'expanded'    => false,
            'multiple'    => true,
            'empty_value' => false,
            'form_type'   => null,
        ]);

        $resolver->setOptional(['form_type']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form_list';
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }
}
