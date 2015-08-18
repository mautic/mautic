<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class UpdateLeadActionType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class UpdateLeadActionType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory       $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory    = $factory;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->factory->getModel('lead.field');
        $leadFields = $fieldModel->getFieldList(false, false);

        foreach ($leadFields as $key => $label) {
            $builder->add($key, 'text', array(
                'label'      => $label,
                'label_attr' => array('class' => 'control-label'),
                'required'   => false,
                'attr'       => array(
                    'class'       => 'form-control'
                )
            ));
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "updatelead_action";
    }
}