<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardEmailsInTimeWidgetType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class DashboardEmailsInTimeWidgetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('flag', 'choice', array(
                'label'   => 'mautic.email.flag.filter',
                'choices' => array(
                    ''                           => 'mautic.email.flag.sent',
                    'opened'                     => 'mautic.email.flag.opened',
                    'failed'                     => 'mautic.email.flag.failed',
                    'sent_and_opened'            => 'mautic.email.flag.sent.and.opened',
                    'sent_and_opened_and_failed' => 'mautic.email.flag.sent.and.opened.and.failed',
                ),
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'empty_data' => '',
                'required'   => false
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "email_dashboard_emails_in_time_widget";
    }
}
