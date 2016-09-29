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
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper;

/**
 * Class UpdateLeadActionType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class UpdateLeadActionType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

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
        $leadFields = $fieldModel->getEntities(
            array(
                'force'          => array(
                    array(
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true
                    )
                ),
                'hydration_mode' => 'HYDRATE_ARRAY'
            )
        );

        $options['fields'] = $leadFields;
        $options['ignore_required_constraints'] = true;

        $this->getFormFields($builder, $options);
    }

    /**
     * @return string
     */
    public function getName() {
        return "updatelead_action";
    }
}