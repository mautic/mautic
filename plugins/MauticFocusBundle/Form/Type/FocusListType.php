<?php

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FocusListType.
 */
class FocusListType extends AbstractType
{
    /**
     * @var FocusRepository
     */
    private $repo;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->repo = $factory->getModel('focus')->getRepository();
        $this->repo->setCurrentUser($factory->getUser());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    $choices = [];

                    $list = $this->repo->getFocusList($options['data']);
                    foreach ($list as $row) {
                        $choices[$row['id']] = $row['name'];
                    }

                    //sort by language
                    ksort($choices);

                    return $choices;
                },
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'empty_value' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.focus.no.focusitem.note' : 'mautic.core.form.chooseone';
                },
                'disabled' => function (Options $options) {
                    return empty($options['choices']);
                },
                'top_level'      => 'variant',
                'variant_parent' => null,
                'ignore_ids'     => [],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'focus_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}