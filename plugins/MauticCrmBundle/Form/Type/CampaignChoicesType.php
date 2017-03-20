<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CampaignChoicesType.
 */
class CampaignChoicesType extends AbstractType
{
    private $integrationHelper;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->model = $factory->getModel('lead.list');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        /** @var \Mautic\LeadBundle\Model\ListModel $model */
        $model = $this->model;
        $resolver->setDefaults([
            'choices' => function (Options $options) use ($model) {
                $lists = (empty($options['global_only'])) ? $model->getUserLists() : $model->getGlobalLists();

                $choices = [];
                foreach ($lists as $l) {
                    $choices[$l['id']] = $l['name'];
                }

                return $choices;
            },
            'global_only' => false,
            'required'    => false,
        ]);
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadlist_choices';
    }
}
