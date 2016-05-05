<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SparkpostType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class SparkpostType extends AbstractType
{

    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct (MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
        $helper = $this->factory->getHelper('integration');

        /** @var \MauticPlugin\MauticEmailMarketingBundle\Integration\SparkpostIntegration $sparkpost */
        $sparkpost = $helper->getIntegrationObject('Sparkpost');

        // Uses sparkpost api to get a list of mailing recipient lists.
        $api = $sparkpost->getApiHelper();
        try {
            $lists   = $api->getLists();
            $choices = array();
            if (!empty($lists)) {
                if ($lists['results']) {
                    foreach ($lists['results'] as $list) {
                        $choices[$list['id']] = $list['name'];
                    }
                }
                asort($choices);
            }
        } catch (\Exception $e) {
            $choices = array();
            $error   = $e->getMessage();
        }

        $builder->add('list', 'choice', array(
            'choices'  => $choices,
            'label'    => 'mautic.sparkpost.list',
            'required' => false,
            'attr'     => array(
                'tooltip'  => 'mautic.sparkpost.list.tooltip',
                'onchange' => 'Mautic.getIntegrationLeadFields(\'Sparkpost\', this, {"list": this.value});'
            )
        ));

        $builder->add('doubleOptin', 'yesno_button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.sparkpost.double_optin',
            'data'        => (!isset($options['data']['doubleOptin'])) ? true : $options['data']['doubleOptin']
        ));

        $builder->add('sendWelcome', 'yesno_button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.emailmarketing.send_welcome',
            'data'        => (!isset($options['data']['sendWelcome'])) ? true : $options['data']['sendWelcome']
        ));

        if (!empty($error)) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($error) {
                $form = $event->getForm();

                if ($error) {
                    $form['list']->addError(new FormError($error));
                }
            });
        }

        if (isset($options['form_area']) && $options['form_area'] == 'integration') {
            $leadFields = $this->factory->getModel('plugin')->getLeadFields();

            $formModifier = function (FormInterface $form, $data) use ($sparkpost, $leadFields) {
                $settings = array(
                    'silence_exceptions' => false,
                    'feature_settings'   => array(
                        'list_settings' => $data
                    )
                );

                try {
                    $fields = $sparkpost->getFormLeadFields($settings);

                    if (!is_array($fields)) {
                        $fields = array();
                    }
                    $error = '';
                } catch (\Exception $e) {
                    $fields = array();
                    $error  = $e->getMessage();
                }

                list ($specialInstructions, $alertType) = $sparkpost->getFormNotes('leadfield_match');
                $form->add('leadFields', 'integration_fields', array(
                    'label'                => 'mautic.integration.leadfield_matches',
                    'required'             => true,
                    'lead_fields'          => $leadFields,
                    'data'                 => isset($data['leadFields']) ? $data['leadFields'] : array(),
                    'integration_fields'   => $fields,
                    'special_instructions' => $specialInstructions,
                    'alert_type'           => $alertType
                ));

                if ($error) {
                    $form->addError(new FormError($error));
                }
            };

            $builder->addEventListener(FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    $formModifier($event->getForm(), $data);
                }
            );

            $builder->addEventListener(FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    $formModifier($event->getForm(), $data);
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('form_area'));
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return "emailmarketing_sparkpost";
    }
}