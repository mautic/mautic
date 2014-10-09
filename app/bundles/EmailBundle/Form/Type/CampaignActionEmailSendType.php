<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CampaignActionEmailSendType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class CampaignActionEmailSendType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('emails', 'email_list', array(
            'label'      => 'mautic.email.form.sendemails',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.email.form.sendemails_descr'
            ),
            'multiple'   => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaignaction_email";
    }
}