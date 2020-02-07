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
use Mautic\AssetBundle\Form\Type\AssetListType;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\PageBundle\Form\Type\PageListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TweetType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
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
            TextareaType::class,
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
            TextareaType::class,
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
                AssetListType::class,
                [
                    'label'       => 'mautic.social.monitoring.twitter.assets',
                    'placeholder' => 'mautic.social.monitoring.list.choose',
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
                PageListType::class,
                [
                    'label'       => 'mautic.social.monitoring.twitter.pages',
                    'placeholder' => 'mautic.social.monitoring.list.choose',
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
            ButtonType::class,
            [
                'label' => 'mautic.social.twitter.handle',
                'attr'  => [
                    'class' => 'form-control btn-primary tweet-insert-handle',
                ],
            ]
        );

        //add category
        $builder->add('category', CategoryListType::class, [
            'bundle' => 'plugin:mauticSocial',
        ]);

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text' => false,
                ]
            );
            $builder->add(
                'updateSelect',
                HiddenType::class,
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['update_select']);
    }

    public function getBlockPrefix()
    {
        return 'twitter_tweet';
    }
}
