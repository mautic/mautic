<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class UserListType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class StageListType extends AbstractType
{

    private $choices = array();

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $choices = $factory->getModel('stage')->getRepository()->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'p.isPublished',
                        'expr'   => 'eq',
                        'value'  => true
                    )
                )
            )
        ));

        foreach ($choices as $choice) {
            $this->choices[$choice->getId()] = $choice->getName(true);
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices'       => $this->choices,
            'empty_value'   => false,
            'expanded'      => false,
            'multiple'      => true,
            'required'      => false,
            'empty_value'   => 'mautic.core.form.chooseone'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "stage_list";
    }

    public function getParent()
    {
        return 'choice';
    }
}