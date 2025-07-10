<?php

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\AssetBundle\Form\Type\AssetListType;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\PageBundle\Form\Type\PageListType;
use MauticPlugin\MauticSocialBundle\Entity\Tweet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<Tweet>
 */
class TweetType extends AbstractType
{
    public function __construct(
        protected EntityManager $em,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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

        $transformer = new IdToEntityModelTransformer($this->em, \Mautic\AssetBundle\Entity\Asset::class, 'id');
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

        $transformer = new IdToEntityModelTransformer($this->em, \Mautic\PageBundle\Entity\Page::class, 'id');
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

        // add category
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['update_select']);
    }

    public function getBlockPrefix(): string
    {
        return 'twitter_tweet';
    }
}
