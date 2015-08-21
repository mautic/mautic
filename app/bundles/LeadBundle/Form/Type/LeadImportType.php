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
            'attr'  => array(
                'class' => 'form-control'
            )
        ));

        $constraints = array(
            new \Symfony\Component\Validator\Constraints\NotBlank(
                array('message' => 'mautic.core.value.required')
            )
        );

        $default = (empty($options['data']['delimiter'])) ? "," : htmlspecialchars($options['data']['delimiter']);
        $builder->add('delimiter', 'text', array(
            'label'       => 'mautic.lead.import.delimiter',
            'attr'        => array(
                'class' => 'form-control'
            ),
            'data'        => $default,
            'constraints' => $constraints
        ));

        $default = (empty($options['data']['enclosure'])) ? '&quot;' : htmlspecialchars($options['data']['enclosure']);
        $builder->add('enclosure', 'text', array(
            'label'       => 'mautic.lead.import.enclosure',
            'attr'        => array(
                'class' => 'form-control'
            ),
            'data'        => $default,
            'constraints' => $constraints
        ));

        $default = (empty($options['data']['escape'])) ? '\\' : $options['data']['escape'];
        $builder->add('escape', 'text', array(
            'label'       => 'mautic.lead.import.escape',
            'attr'        => array(
                'class' => 'form-control'
            ),
            'data'        => $default,
            'constraints' => $constraints
        ));

        $default = (empty($options['data']['batchlimit'])) ? 100 : (int) $options['data']['batchlimit'];
        $builder->add('batchlimit', 'text', array(
            'label'       => 'mautic.lead.import.batchlimit',
            'attr'        => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.lead.import.batchlimit_tooltip'
            ),
            'data'        => $default,
            'constraints' => $constraints
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
    public function getName ()
    {
        return "lead_import";
    }
}
