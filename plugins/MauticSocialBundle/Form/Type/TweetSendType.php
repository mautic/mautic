<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class TweetSendType.
 */
class TweetSendType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'channelId',
            'tweet_list',
            [
                'label'      => 'mautic.integration.Twitter.send.selecttweet',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.integration.Twitter.send.selecttweet.desc',
                    'onchange' => 'Mautic.disabledTweetAction()',
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.integration.Twitter.send.selecttweet.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate(
                'mautic_tweet_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newTweetButton',
                'button',
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon' => 'fa fa-plus',
                    ],
                    'label' => 'mautic.integration.Twitter.new.tweet',
                ]
            );

            // $tweet = $options['data']['channelId'];

            // create button edit tweet
            // @todo: this button requires a JS to be injected to the campaign builder
            // $windowUrlEdit = $this->router->generate(
            //     'mautic_tweet_action',
            //     [
            //         'objectAction' => 'edit',
            //         'objectId'     => 'tweetId',
            //         'contentOnly'  => 1,
            //         'updateSelect' => $options['update_select'],
            //     ]
            // );

            // $builder->add(
            //     'editTweetButton',
            //     'button',
            //     [
            //         'attr' => [
            //             'class'    => 'btn btn-primary btn-nospin',
            //             'onclick'  => 'Mautic.loadNewWindow(Mautic.standardTweetUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
            //             'disabled' => !isset($tweet),
            //             'icon'     => 'fa fa-edit',
            //         ],
            //         'label' => 'mautic.integration.Twitter.edit.tweet',
            //     ]
            // );
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['update_select']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'tweetsend_list';
    }
}
