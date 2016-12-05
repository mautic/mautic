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

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CategoryType.
 */
class CategoryType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Session
     */
    private $session;

    /**
     * CategoryType constructor.
     *
     * @param TranslatorInterface $translator
     * @param Session             $session
     */
    public function __construct(TranslatorInterface $translator, Session $session)
    {
        $this->translator = $translator;
        $this->session    = $session;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('category.category', $options));

        if (!$options['data']->getId()) {
            // Do not allow custom bundle
            if ($options['show_bundle_select'] == true) {
                // Create new category from category bundle - let user select the bundle
                $selected = $this->session->get('mautic.category.type', 'category');
                $builder->add(
                    'bundle',
                    'category_bundles_form',
                    [
                        'label'      => 'mautic.core.type',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => ['class' => 'form-control'],
                        'required'   => true,
                        'data'       => $selected,
                    ]
                );
            } else {
                // Create new category directly from another bundle - preset bundle
                $builder->add(
                    'bundle',
                    'hidden',
                    [
                        'data' => $options['bundle'],
                    ]
                );
            }
        }

        $builder->add(
            'title',
            'text',
            [
                'label'      => 'mautic.core.title',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            'text',
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'alias',
            'text',
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.category.form.alias.help',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'color',
            'text',
            [
                'label'      => 'mautic.core.color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                ],
                'required' => false,
            ]
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'inForm',
            'hidden',
            [
                'mapped' => false,
            ]
        );

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Mautic\CategoryBundle\Entity\Category',
                'show_bundle_select' => false,
                'bundle'             => function (Options $options) {
                    if (!$bundle = $options['data']->getBundle()) {
                        $bundle = 'category';
                    }

                    return $bundle;
                },
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'category_form';
    }
}
