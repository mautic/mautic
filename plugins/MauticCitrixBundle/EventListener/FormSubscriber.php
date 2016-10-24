<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use AppKernel;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;

/**
 * Class FormSubscriber
 */
class FormSubscriber extends CommonSubscriber
{

    /** @var EntityManager $entityManager */
    protected $entityManager;

    /** @var AppKernel $kernel */
    protected $kernel;

    /**
     * FormSubscriber constructor.
     * @param EntityManager $entityManager
     * @param AppKernel $kernel
     */
    public function __construct(EntityManager $entityManager, AppKernel $kernel)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            FormEvents::FORM_ON_BUILD => ['onFormBuilder', 0],
            CitrixEvents::ON_FORM_SUBMIT_ACTION => ['onFormSubmit', 0],
            CitrixEvents::ON_FORM_VALIDATE_ACTION => ['onFormValidate', 0],
            FormEvents::FORM_PRE_SAVE => array('onFormPreSave', 0),
            // TODO: Remove onRequest event
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => ['onRequest', 0],
        );
    }

    /**
     * Helper function to debug REST requests
     * TODO: Remove onRequest function
     *
     * @param PluginIntegrationRequestEvent $event
     */
    public function onRequest(PluginIntegrationRequestEvent $event)
    {
        CitrixHelper::log('error', "URL:\n".$event->getUrl()."\n\n");
        CitrixHelper::log('error', "HEADERS:\n".print_r($event->getHeaders(), true)."\n\n");
        CitrixHelper::log('error', "PARAMS:\n".print_r($event->getParameters(), true)."\n\n");
        CitrixHelper::log('error', "SETTINGS:\n".print_r($event->getSettings(), true)."\n\n");
    }

    /**
     * @param Events\FormEvent $event
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     */
    public function onFormPreSave(Events\FormEvent $event)
    {
        $activeProducts = [];

        if (CitrixHelper::isAuthorized('Gotowebinar')) {
            $activeProducts[] = CitrixProducts::GOTOWEBINAR;
        }

        if (CitrixHelper::isAuthorized('Gotomeeting')) {
            $activeProducts[] = CitrixProducts::GOTOMEETING;
        }

        if (CitrixHelper::isAuthorized('Gototraining')) {
            $activeProducts[] = CitrixProducts::GOTOTRAINING;
        }

        if (CitrixHelper::isAuthorized('Gotoassist')) {
            $activeProducts[] = CitrixProducts::GOTOASSIST;
        }

        if (0 === count($activeProducts)) {
            return;
        }

        $form = $event->getForm();
        $fields = $form->getFields()->getValues();

        if (0 !== count($fields)) {
            foreach ($activeProducts as $product) {
                $hasCitrixlistFields = false;
                /** @var Field $field */
                foreach ($fields as $field) {
                    if ($field->getType() === 'plugin.citrix.select.'.$product) {
                        $properties = $field->getProperties();
                        $properties[$product.'list_serialized'] = serialize(CitrixHelper::getCitrixChoices($product));
                        $field->setProperties($properties);
                        $this->entityManager->persist($field);
                        $hasCitrixlistFields = true;
                    }
                }
                if ($hasCitrixlistFields) {
                    $this->entityManager->flush();
                }
            }

            $errors = $this->_checkFormValidity($activeProducts, $form);
            $errorSeparator = '~ Citrix';
            $formName = $form->getName();
            $newFormName = trim(explode($errorSeparator, $formName)[0]);
            if (0 !== count($errors)) {
                $newFormName .= ' '.$errorSeparator.' '.$errors[0];
            }
            if ($newFormName !== $formName) {
                $form->setName($newFormName);
                $this->entityManager->persist($form);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param array $activeProducts
     * @param Form $form
     * @return array
     * @throws \InvalidArgumentException
     */
    private function _checkFormValidity(array $activeProducts, Form $form)
    {
        $errors = [];

        $errorMessages = [
            'lead_field_not_found' => $this->translator->trans('plugin.citrix.formaction.validator.leadfieldnotfound'),
            'field_not_found' => $this->translator->trans('plugin.citrix.formaction.validator.fieldnotfound'),
            'field_should_be_required' => $this->translator->trans(
                'plugin.citrix.formaction.validator.fieldshouldberequired'
            ),
        ];

        $actions = $form->getActions();
        $hasCitrixAction = false;
        if (null !== $actions) {
            /** @var Action $action */
            foreach ($actions as $action) {
                if ($action->getType() === 'mautic.form.type.citrix.submitaction') {
                    $hasCitrixAction = true;
                    break;
                }
            }
        }

        if ($hasCitrixAction) {
            $fields = $form->getFields();
            foreach ($activeProducts as $product) {
                $currentLeadFields = array();
                $hasCitrixListField = false;
                if (null !== $fields) {
                    /** @var Field $field */
                    foreach ($fields as $field) {
                        $leadField = $field->getLeadField();
                        if ('' !== $leadField) {
                            $currentLeadFields[$leadField] = $field->getIsRequired();
                        }

                        if ($field->getType() === 'plugin.citrix.'.$product.'.listfield') {
                            $hasCitrixListField = true;
                            if (!$field->getIsRequired()) {
                                $errors[] = sprintf($errorMessages['field_should_be_required'], $product.'-list');
                            }
                        }
                    }
                }

                if (!$hasCitrixListField) {
                    $errors[] = sprintf($errorMessages['field_not_found'], $product.'-list');
                }

                $mandatoryFields = array('email', 'firstname', 'lastname');
                foreach ($mandatoryFields as $mandatoryField) {
                    if (!array_key_exists($mandatoryField, $currentLeadFields)) {
                        $errors[] = sprintf($errorMessages['lead_field_not_found'], $mandatoryField);
                    } else {
                        if (!$currentLeadFields[$mandatoryField]) {
                            $errors[] = sprintf($errorMessages['field_should_be_required'], $mandatoryField);
                        }
                    }
                }
            } // foreach $product
        }

        return $errors;
    }

    /**
     *
     * @param Events\ValidationEvent $event
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $translator = CitrixHelper::getContainer()->get('translator');
        $field = $event->getField();
        $eventType = preg_filter('/^plugin\.citrix\.select\.(.*)$/', '$1', $field->getType());
        $doValidation = CitrixHelper::isAuthorized('Goto'.$eventType);

        if ($doValidation) {
            $list = CitrixHelper::getCitrixChoices($eventType);
            /** @var array $values */
            $values = $event->getValue();
            if (!(array)$values) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if (!array_key_exists($value, $list)) {
                    $event->failedValidation(
                        $value.': '.$translator->trans('plugin.citrix.'.$eventType.'.nolongeravailable')
                    );
                }
            }
        }
    }

    /**
     * @param Collection $fields
     * @param array $post
     * @param array $webinarlist
     * @return array
     */
    private static function _getWebinarsFromPost($fields, $post, $webinarlist)
    {
        $webinars = array();
        /** @var \Mautic\FormBundle\Entity\Field $field */
        foreach ($fields as $field) {
            if ('plugin.citrix.select.webinar' === $field->getType()) {
                $alias = $field->getAlias();
                $webinarIds = $post[$alias];
                if (!(array)$webinarIds) {
                    $webinarIds = [$webinarIds];
                }
                /** @var array $webinarIds */
                foreach ($webinarIds as $webinarId) {
                    $webinars[] = array(
                        'fieldName' => $alias,
                        'webinarId' => $webinarId,
                        'webinarTitle' => array_key_exists(
                            $webinarId,
                            $webinarlist
                        ) ? $webinarlist[$webinarId] : 'untitled',
                    );
                }
            }
        }

        return $webinars;
    }

    /**
     *
     * @param Events\SubmissionEvent $event
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onFormSubmit(Events\SubmissionEvent $event)
    {
        $submission = $event->getSubmission();
        $form = $submission->getForm();
        $actions = $form->getActions();
        $post = $event->getPost();
        $fields = $form->getFields();

        /** @var \Mautic\FormBundle\Entity\Action $action */
        foreach ($actions as $action) {
            if (CitrixHelper::isAuthorized('Gotowebinar') &&
                'plugin.citrix.subscribe.webinar' === $action->getType()
            ) {
                /** @var array $webinars */
                $webinarlist = CitrixHelper::getCitrixChoices('webinar');
                $webinarsToRegister = self::_getWebinarsFromPost($fields, $post, $webinarlist);
                if (0 !== count($webinarsToRegister)) {
                    $results = $submission->getResults();
                    foreach ($webinarsToRegister as $webinar) {
                        $results[$webinar['fieldName']] = $webinar['webinarTitle'].' ('.$webinar['webinarId'].')';
                    }
                    $submission->setResults($results); // make post results readable
                    /** @var LeadModel $leadModel */
                    $leadModel = CitrixHelper::getContainer()->get('mautic.model.factory')->getModel('lead');
                    /** @var Lead $currentLead */
                    $currentLead = $leadModel->getCurrentLead();
                    if ($currentLead instanceof Lead) {
                        $leadFields = $currentLead->getProfileFields();
                        list($email, $firstname, $lastname) = [
                            array_key_exists('email', $leadFields) ? $leadFields['email'] : '',
                            array_key_exists('firstname', $leadFields) ? $leadFields['firstname'] : '',
                            array_key_exists('lastname', $leadFields) ? $leadFields['lastname'] : '',
                        ];

                        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
                            foreach ($webinarsToRegister as $webinar) {
                                $webinarId = $webinar['webinarId'];
                                $isRegistered = CitrixHelper::registerToWebinar(
                                    $webinarId,
                                    $email,
                                    $firstname,
                                    $lastname
                                );
                                if ($isRegistered) {
                                    $eventName = CitrixHelper::getCleanString(
                                            $webinar['webinarTitle']
                                        ).'_#'.$webinar['webinarId'];
                                    /** @var CitrixModel $citrixModel */
                                    $citrixModel = CitrixHelper::getContainer()->get('mautic.model.factory')->getModel(
                                        'mautic.citrix.model.citrix'
                                    );
                                    try {
                                        $citrixModel->addEvent(CitrixProducts::GOTOWEBINAR, $email, $eventName, CitrixEventTypes::REGISTERED);
                                    } catch (\Exception $ex) {

                                    }
                                }
                            }
                        }
                    }
                } // end-block
            } // webinar

            if (CitrixHelper::isAuthorized('Gotomeeting') &&
                'plugin.citrix.start.meeting' === $action->getType()
            ) {
                /** @var \Mautic\FormBundle\Entity\Field $field */
                foreach ($form->getFields() as $field) {
                    if ('plugin.citrix.select.meeting' === $field->getType()) {
                        $meetingId = $post[$field->getAlias()];
                    }
                }
            }

            if (CitrixHelper::isAuthorized('Gototraining')) {
                /** @var \Mautic\FormBundle\Entity\Field $field */
                foreach ($form->getFields() as $field) {
                    if ('plugin.citrix.select.training' === $field->getType()) {
                        $trainingId = $post[$field->getAlias()];

                        if ('plugin.citrix.subscribe.training' === $action->getType()
                        ) {

                        } else {
                            if ('plugin.citrix.start.training' === $action->getType()
                            ) {

                            }
                        }
                    }
                }
            }

            if (CitrixHelper::isAuthorized('Gotoassist')) {
                /** @var \Mautic\FormBundle\Entity\Field $field */
                foreach ($form->getFields() as $field) {
                    if ('plugin.citrix.select.assist' === $field->getType()) {
                        $sessionId = $post[$field->getAlias()];

                        if ('plugin.citrix.attend.assist' === $action->getType()
                        ) {

                        } else {
                            if ('plugin.citrix.screensharing.assist' === $action->getType()
                            ) {

                            } else {
                                if ('plugin.citrix.webcast.assist' === $action->getType()
                                ) {

                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @param Events\FormBuilderEvent $event
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        if (CitrixHelper::isAuthorized('Gotowebinar')) {
            $action = [
                'group' => 'plugin.citrix.webinar.header',
                'description' => 'plugin.citrix.webinar.header.tooltip',
                'label' => 'plugin.citrix.webinar.action.subscribe',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.subscribe.webinar', $action);

            $field = [
                'label' => 'plugin.citrix.webinar.listfield',
                'formType' => 'citrix_list',
                'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
                'listType' => 'webinar',
            ];
            $event->addFormField('plugin.citrix.select.webinar', $field);

            $validator = [
                'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.select.webinar',
            ];
            $event->addValidator('plugin.citrix.validate.webinar', $validator);
        }

        if (CitrixHelper::isAuthorized('Gotomeeting')) {
            $action = [
                'group' => 'plugin.citrix.meeting.header',
                'description' => 'plugin.citrix.meeting.header.tooltip',
                'label' => 'plugin.citrix.meeting.action.start',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.start.meeting', $action);

            $field = [
                'label' => 'plugin.citrix.meeting.listfield',
                'formType' => 'citrix_list',
                'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
                'listType' => 'meeting',
            ];
            $event->addFormField('plugin.citrix.select.meeting', $field);

            $validator = [
                'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.select.meeting',
            ];
            $event->addValidator('plugin.citrix.validate.meeting', $validator);
        }

        if (CitrixHelper::isAuthorized('Gototraining')) {
            $action = [
                'group' => 'plugin.citrix.training.header',
                'description' => 'plugin.citrix.training.header.tooltip',
                'label' => 'plugin.citrix.training.action.subscribe',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.subscribe.training', $action);

            $action = [
                'group' => 'plugin.citrix.training.header',
                'description' => 'plugin.citrix.training.header.tooltip',
                'label' => 'plugin.citrix.training.action.start',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.start.training', $action);

            $field = [
                'label' => 'plugin.citrix.training.listfield',
                'formType' => 'citrix_list',
                'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
                'listType' => 'training',
            ];
            $event->addFormField('plugin.citrix.select.training', $field);

            $validator = [
                'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.select.training',
            ];
            $event->addValidator('plugin.citrix.validate.training', $validator);
        }

        if (CitrixHelper::isAuthorized('Gotoassist')) {
            $action = [
                'group' => 'plugin.citrix.assist.header',
                'description' => 'plugin.citrix.assist.header.tooltip',
                'label' => 'plugin.citrix.assist.action.attended',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.attended.assist', $action);

            $action = [
                'group' => 'plugin.citrix.assist.header',
                'description' => 'plugin.citrix.assist.header.tooltip',
                'label' => 'plugin.citrix.assist.action.screensharing',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.screensharing.assist', $action);

            $action = [
                'group' => 'plugin.citrix.assist.header',
                'description' => 'plugin.citrix.assist.header.tooltip',
                'label' => 'plugin.citrix.assist.action.webchat',
                'formType' => 'citrix_submit_action',
                'template' => 'MauticFormBundle:Action:generic.html.php',
                'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
            ];
            $event->addSubmitAction('plugin.citrix.webchat.assist', $action);

            $field = [
                'label' => 'plugin.citrix.assist.listfield',
                'formType' => 'citrix_list',
                'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
                'listType' => 'assist',
            ];
            $event->addFormField('plugin.citrix.select.assist', $field);

            $validator = [
                'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.select.assist',
            ];
            $event->addValidator('plugin.citrix.validate.assist', $validator);
        }
    }

}