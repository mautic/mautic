<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SmsListType.
 */
class SmsListType extends AbstractType
{
    private $repo;
    private $viewOther;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->viewOther = $factory->getSecurity()->isGranted('sms:smses:viewother');
        $this->repo      = $factory->getModel('sms')->getRepository();

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

                    $smses = $repo->getSmsList('', 0, 0, $viewOther);
                    foreach ($smses as $sms) {
                        $choices[$sms['language']][$sms['id']] = $sms['name'];
                    }

                    //sort by language
                    ksort($choices);

                    return $choices;
                },
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'empty_value' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.sms.no.smses.note' : 'mautic.core.form.chooseone';
                },
                'sms_type' => 'template',
                'disabled' => function (Options $options) {
                    return empty($options['choices']);
                },
            ]
        );

        $resolver->setOptional(['sms_type']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sms_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
