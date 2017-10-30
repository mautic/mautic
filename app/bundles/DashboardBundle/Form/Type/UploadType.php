<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class UploadType.
 */
class UploadType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file', [
            'label' => 'mautic.lead.import.file',
            'attr'  => [
                'accept' => '.json',
                'class'  => 'form-control',
            ],
        ]);
        $constraints = [
            new \Symfony\Component\Validator\Constraints\NotBlank(
                ['message' => 'mautic.core.value.required']
            ),
        ];
        $builder->add('start', 'submit', [
            'attr' => [
                'class'   => 'btn btn-primary',
                'icon'    => 'fa fa-upload',
                'onclick' => "mQuery(this).prop('disabled', true); mQuery('form[name=\'dashboard_upload\']').submit();",
            ],
            'label' => 'mautic.lead.import.upload',
        ]);
        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dashboard_upload';
    }
}
