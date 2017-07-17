<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class TweetType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.social.monitoring.twitter.tweet.name',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip' => 'mautic.social.monitoring.twitter.tweet.name.tooltip',
                    'class'   => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.name.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'description',
            'textarea',
            [
                'label'      => 'mautic.social.monitoring.twitter.tweet.description',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip' => 'mautic.social.monitoring.twitter.tweet.description.tooltip',
                    'class'   => 'form-control',
                ],
            ]
        );

        $builder->add(
            'text',
            'textarea',
            [
                'label'      => 'mautic.social.monitoring.twitter.tweet.text',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip' => 'mautic.social.monitoring.twitter.tweet.text.tooltip',
                    'class'   => 'form-control tweet-message',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticAssetBundle:Asset', 'id');
        $builder->add(
                $builder->create(
                'asset',
                'asset_list',
                [
                    'label'       => 'mautic.social.monitoring.twitter.assets',
                    'empty_value' => 'mautic.social.monitoring.list.choose',
                    'label_attr'  => ['class' => 'control-label'],
                    'multiple'    => false,
                    'attr'        => [
                        'class'   => 'form-control tweet-insert-asset',
                        'tooltip' => 'mautic.social.monitoring.twitter.assets.descr',
                    ],
                ]
            )->addModelTransformer($transformer)
        );

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticPageBundle:Page', 'id');
        $builder->add(
            $builder->create(
                'page',
                'page_list',
                [
                    'label'       => 'mautic.social.monitoring.twitter.pages',
                    'empty_value' => 'mautic.social.monitoring.list.choose',
                    'label_attr'  => ['class' => 'control-label'],
                    'multiple'    => false,
                    'attr'        => [
                        'class'   => 'form-control tweet-insert-page',
                        'tooltip' => 'mautic.social.monitoring.twitter.pages.descr',
                    ],
                ]
            )->addModelTransformer($transformer)
        );

        $builder->add(
            'handle',
            'button',
            [
                'label' => 'mautic.social.twitter.handle',
                'attr'  => [
                    'class' => 'form-control btn-primary tweet-insert-handle',
                ],
            ]
        );

        //add category
        $builder->add('category', 'category', [
            'bundle' => 'plugin:mauticSocial',
        ]);

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                'form_buttons',
                [
                    'apply_text' => false,
                ]
            );
            $builder->add(
                'updateSelect',
                'hidden',
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                'form_buttons'
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['update_select']);
    }

    public function getName()
    {
        return 'twitter_tweet';
    }
}
