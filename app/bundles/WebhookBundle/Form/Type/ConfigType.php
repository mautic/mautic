<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 *
 * @package Mautic\WebhookBundle\Form\Type
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('queue_mode', 'choice', array(
            'choices'  => array(
                'immediate_process' => 'mautic.webhook.config.immediate_process',
                'command_process'   => 'mautic.webhook.config.cron_process'
            ),
            'label'    => 'mautic.webhook.config.form.queue.mode',
            'attr'     => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.webhook.config.form.queue.mode.tooltip'
            ),
            'empty_value' => false,
            'constraints' => array(
                new NotBlank(
                    array(
                        'message' => 'mautic.core.value.required'
                    )
                )
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'webhookconfig';
    }
}