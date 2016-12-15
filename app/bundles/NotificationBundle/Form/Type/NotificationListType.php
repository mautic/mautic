<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class NotificationListType.
 */
class NotificationListType extends AbstractType
{
    private $repo;
    private $viewOther;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->viewOther = $factory->getSecurity()->isGranted('notification:notifications:viewother');
        $this->repo      = $factory->getModel('notification')->getRepository();

        $this->repo->setCurrentUser($factory->getUser());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $viewOther = $this->viewOther;
        $repo      = $this->repo;

        $resolver->setDefaults(
            [
                'choices' => function (Options $options) use ($repo, $viewOther) {
                    static $choices;

                    if (is_array($choices)) {
                        return $choices;
                    }

                    $choices = [];

                    $notifications = $repo->getNotificationList('', 0, 0, $viewOther);
                    foreach ($notifications as $notification) {
                        $choices[$notification['language']][$notification['id']] = $notification['name'];
                    }

                    //sort by language
                    ksort($choices);

                    return $choices;
                },
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'empty_value' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.notification.no.notifications.note' : 'mautic.core.form.chooseone';
                },
                'notification_type' => 'template',
                'disabled'          => function (Options $options) {
                    return empty($options['choices']);
                },
            ]
        );

        $resolver->setOptional(['notification_type']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'notification_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
