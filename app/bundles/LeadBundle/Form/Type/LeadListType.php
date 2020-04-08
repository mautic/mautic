<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeadListType extends AbstractType
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    public function __construct(ListModel $segmentModel)
    {
        $this->segmentModel = $segmentModel;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                'choices'           => function (Options $options) {
                    $lists = (empty($options['global_only'])) ? $this->segmentModel->getUserLists() : $this->segmentModel->getGlobalLists();
                    $lists = (empty($options['preference_center_only'])) ? $lists : $this->segmentModel->getPreferenceCenterLists();

                    $choices = [];
                    foreach ($lists as $l) {
                        $choices[$l['name']] = $l['id'];
                    }

                    return $choices;
                },
            'global_only'            => false,
            'preference_center_only' => false,
            'required'               => false,
        ]);
    }

    /**
     * @return string|FormTypeInterface|null
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_choices';
    }
}
