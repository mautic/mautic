<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class MailchimpType.
 */
class MailchimpType extends AbstractType
{
    /**
     * @var MauticFactory
     */
    private $factory;
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function __construct(MauticFactory $factory, Session $session, CoreParametersHelper $coreParametersHelper)
    {
        $this->factory              = $factory;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
        $helper = $this->factory->getHelper('integration');

        /** @var \MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration $mailchimp */
        $mailchimp = $helper->getIntegrationObject('Mailchimp');

        $api = $mailchimp->getApiHelper();
        try {
            $lists   = $api->getLists();
            $choices = [];
            if (!empty($lists)) {
                if ($lists['total_items']) {
                    foreach ($lists['lists'] as $list) {
                        $choices[$list['id']] = $list['name'];
                    }
                }

                asort($choices);
            }
        } catch (\Exception $e) {
            $choices = [];
            $error   = $e->getMessage();
        }

        $builder->add('list', 'choice', [
            'choices'  => $choices,
            'label'    => 'mautic.emailmarketing.list',
            'required' => false,
            'attr'     => [
                'tooltip'  => 'mautic.emailmarketing.list.tooltip',
                'onchange' => 'Mautic.getIntegrationLeadFields(\'Mailchimp\', this, {"list": this.value});',
            ],
        ]);

        $builder->add('doubleOptin', 'yesno_button_group', [
            'label' => 'mautic.mailchimp.double_optin',
            'data'  => (!isset($options['data']['doubleOptin'])) ? true : $options['data']['doubleOptin'],
        ]);

        $builder->add('sendWelcome', 'yesno_button_group', [
            'label' => 'mautic.emailmarketing.send_welcome',
            'data'  => (!isset($options['data']['sendWelcome'])) ? true : $options['data']['sendWelcome'],
        ]);

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

            $formModifier = function (FormInterface $form, $data) use ($mailchimp, $leadFields) {
                $integrationName = $mailchimp->getName();
                $session         = $this->session;
                $limit           = $session->get(
                    'mautic.plugin.'.$integrationName.'.lead.limit',
                    $this->coreParametersHelper->getParameter('default_pagelimit')
                );
                $page     = $session->get('mautic.plugin.'.$integrationName.'.lead.page', 1);
                $settings = [
                    'silence_exceptions' => false,
                    'feature_settings'   => [
                        'list_settings' => $data,
                    ],
                    'ignore_field_cache' => ($page == 1 && 'POST' !== $_SERVER['REQUEST_METHOD']) ? true : false,
                ];
                try {
                    $fields = $mailchimp->getFormLeadFields($settings);

                    if (!is_array($fields)) {
                        $fields = [];
                    }
                    $error = '';
                } catch (\Exception $e) {
                    $fields = [];
                    $error  = $e->getMessage();
                    $page   = 1;
                }

                list($specialInstructions) = $mailchimp->getFormNotes('leadfield_match');
                $form->add('leadFields', 'integration_fields', [
                    'label'                => 'mautic.integration.leadfield_matches',
                    'required'             => true,
                    'mautic_fields'        => $leadFields,
                    'integration'          => $mailchimp->getName(),
                    'integration_object'   => $mailchimp,
                    'limit'                => $limit,
                    'page'                 => $page,
                    'data'                 => $data,
                    'integration_fields'   => $fields,
                    'special_instructions' => $specialInstructions,
                    'mapped'               => true,
                    'error_bubbling'       => false,
                ]);

                if ($error) {
                    $form->addError(new FormError($error));
                }
            };

            $builder->addEventListener(FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    if (isset($data['leadFields']['leadFields'])) {
                        $data['leadFields'] = $data['leadFields']['leadFields'];
                    }
                    $formModifier($event->getForm(), $data);
                }
            );

            $builder->addEventListener(FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    if (isset($data['leadFields']['leadFields'])) {
                        $data['leadFields'] = $data['leadFields']['leadFields'];
                    }
                    $formModifier($event->getForm(), $data);
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['form_area']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'emailmarketing_mailchimp';
    }
}
