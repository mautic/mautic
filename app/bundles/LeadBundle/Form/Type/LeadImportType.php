<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class LeadImportType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class LeadImportType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file', array(
            'label' => 'mautic.lead.import.file',
            'attr' => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('start', 'submit', array(
            'attr'  => array(
                'class'   => 'btn btn-primary',
                'icon'    => 'fa fa-upload',
                'onclick' => "mQuery(this).prop('disabled', true); mQuery('form[name=\'lead_import\']').submit();"
            ),
            'label' => 'mautic.lead.import.upload'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "lead_import";
    }
}
