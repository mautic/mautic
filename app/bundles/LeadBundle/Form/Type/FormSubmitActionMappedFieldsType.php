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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormSubmitActionMappedFieldsType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FormSubmitActionMappedFieldsType extends AbstractType
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
        static $choices;
        if (empty($choices)) {
            $fields = $this->factory->getModel('form.field')->getSessionFields();

            $choices = array();

            foreach ($fields as $k => $f) {
                //only show fields with ids
                if (strpos($k, 'new') !== false)
                    continue;
                //ignore some types of fields
                if (in_array($f['type'], array('button', 'freetext', 'captcha')))
                    continue;

                $choices[$k] = $f['label'];
            }
        }

        //get a list of fields
        $fields = $this->factory->getModel('lead.field')->getEntities(
            array('filter' => array('isPublished' => true))
        );

        foreach ($fields as $field) {
            $id    = $field->getId();
            $label = $field->getLabel();

            $builder->add($id, 'choice', array(
                'label'      => $label,
                'choices'    => $choices,
                'attr'       => array('class' => 'form-control'),
                'label_attr' => array('class' => 'control-label'),
                'multiple'   => false,
                'expanded'   => false,
                'required'   => false,
                'empty_value' => ''
            ));
        }

    }

    /**
     * @return string
     */
    public function getName() {
        return "lead_submitaction_mappedfields";
    }
}