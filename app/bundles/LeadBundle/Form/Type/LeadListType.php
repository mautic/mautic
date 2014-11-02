<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\StringToDatetimeTransformer;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadListType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class LeadListType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory       $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory    = $factory;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        /** @var \Mautic\LeadBundle\Model\ListModel $model */
        $model = $this->factory->getModel('lead.list');
        $resolver->setDefaults(array(
            'choices' => function (Options $options) use ($model) {
                $lists = (empty($options['global_only'])) ? $model->getUserLists() : $model->getGlobalLists();

                $choices = array();
                foreach ($lists as $l) {
                    $choices[$l['id']] = $l['name'];
                }

                return $choices;
            },
            'global_only' => false
        ));
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
    public function getName() {
        return "leadlist_choices";
    }
}