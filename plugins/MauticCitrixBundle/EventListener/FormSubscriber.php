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
use Mautic\FormBundle\Exception\ValidationException;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
            CitrixEvents::ON_WEBINAR_SUBSCRIBE_ACTION => ['onWebinarSubscribe', 0],
            CitrixEvents::ON_MEETING_START_ACTION => ['onMeetingStart', 0],
            CitrixEvents::ON_TRAINING_SUBSCRIBE_ACTION => ['onTrainingSubscribe', 0],
            CitrixEvents::ON_TRAINING_START_ACTION => ['onTrainingStart', 0],
            CitrixEvents::ON_ASSIST_REMOTE_ACTION => ['onAssistRemote', 0],
            CitrixEvents::ON_ASSIST_WEBCHAT_ACTION => ['onAssistWebchat', 0],
            CitrixEvents::ON_FORM_VALIDATE_ACTION => ['onFormValidate', 0],
            FormEvents::FORM_PRE_SAVE => array('onFormPreSave', 0),
            // TODO: Remove onRequest event
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => ['onRequest', 0],
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE => ['onResponse', 0],
        );
    }

    public function onWebinarSubscribe(Events\SubmissionEvent $event)
    {
        $submission = $event->getSubmission();
        $form = $submission->getForm();
        $post = $event->getPost();
        $fields = $form->getFields();

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
                        try {
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
                                $citrixModel = CitrixHelper::getContainer()->get(
                                    'mautic.model.factory'
                                )->getModel(
                                    'citrix.citrix'
                                );

                                $citrixModel->addEvent(
                                    CitrixProducts::GOTOWEBINAR,
                                    $email,
                                    $eventName,
                                    CitrixEventTypes::REGISTERED
                                );
                            }
                        } catch (\Exception $ex) {
                            CitrixHelper::log('onFormSubmit: '.$ex->getMessage());
                            $validationException = new ValidationException($ex->getMessage());
                            $validationException->setViolations(
                                [
                                    $fields->first()->getAlias() => $ex->getMessage(),
                                ]
                            );
                            throw $validationException;
                        }
                    }
                }
            }
        } // end-block

    }

    public function onMeetingStart(Events\SubmissionEvent $event)
    {
        $form = $event->getForm();
        $post = $event->getPost();
        /** @var \Mautic\FormBundle\Entity\Field $field */
        foreach ($form->getFields() as $field) {
            if ('plugin.citrix.select.meeting' === $field->getType()) {
                $meetingId = $post[$field->getAlias()];
            }
        }
    }

    public function onTrainingSubscribe(Events\SubmissionEvent $event)
    {

    }

    public function onTrainingStart(Events\SubmissionEvent $event)
    {

    }

    public function onAssistRemote(Events\SubmissionEvent $event)
    {

    }

    public function onAssistWebchat(Events\SubmissionEvent $event)
    {

    }

    /**
     * Helper function to debug REST responses
     * TODO: Remove onResponse function
     *
     * @param PluginIntegrationRequestEvent $event
     */
    public function onResponse(PluginIntegrationRequestEvent $event)
    {
        /** @var Response $response */
        $response = $event->getResponse();
        CitrixHelper::log(
            PHP_EOL. //$response->getStatusCode() . ' ' .
            print_r($response, true)
        );
//            print_r($response->headers->all(), true). PHP_EOL .
//            $response->getContent());
    }

    /**
     * Helper function to debug REST requests
     * TODO: Remove onRequest function
     *
     * @param PluginIntegrationRequestEvent $event
     */
    public function onRequest(PluginIntegrationRequestEvent $event)
    {
        CitrixHelper::log(
            PHP_EOL.$event->getMethod().' '.$event->getUrl().' '.
            print_r($event->getParameters(), true)
        );
    }

    /**
     * @param Events\FormEvent $event
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     */
    public function onFormPreSave(Events\FormEvent $event)
    {
        $form = $event->getForm();
        $fields = $form->getFields()->getValues();

        if (0 !== count($fields)) {
            $hasCitrixlistFields = false;
            /** @var Field $field */
            foreach ($fields as $field) {
                $product = preg_filter('/^plugin\.citrix\.select\.(.*)$/', '$1', $field->getType());
                $doValidation = CitrixHelper::isAuthorized('Goto'.$product);
                if ($doValidation) {
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

            $errors = $this->_checkFormValidity($form);
            $errorSeparator = '~ Citrix';
            $formName = $form->getName();
            $newFormName = trim(explode($errorSeparator, $formName)[0]);
            if (0 !== count($errors)) {
                $newFormName .= ' '.$errorSeparator.' '.$errors[0];
                $event->stopPropagation();
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
    private function _checkFormValidity(Form $form)
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
        $actionProduct = 'Citrix List';
        if (null !== $actions) {
            /** @var Action $action */
            foreach ($actions as $action) {
                if (strpos($action->getType(), 'plugin.citrix.action') === 0) {
                    $actionProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $action->getType());
                    $hasCitrixAction = true;
                    break;
                }
            }
        }

        if ($hasCitrixAction) {
            $fields = $form->getFields();
            $currentLeadFields = [];
            $hasCitrixListField = false;
            if (null !== $fields) {
                /** @var Field $field */
                foreach ($fields as $field) {
                    $leadField = $field->getLeadField();
                    if ('' !== $leadField) {
                        $currentLeadFields[$leadField] = $field->getIsRequired();
                    }

                    $product = preg_filter('/^plugin\.citrix\.select\.(.*)$/', '$1', $field->getType());
                    $doValidation = CitrixHelper::isAuthorized('Goto'.$product);
                    if ($doValidation) {
                        $hasCitrixListField = true;
                        if (!$field->getIsRequired()) {
                            $errors[] = sprintf(
                                $errorMessages['field_should_be_required'],
                                $this->translator->trans('plugin.citrix.'.$product.'.listfield')
                            );
                        }
                    }
                }
            }

            if (!$hasCitrixListField) {
                $errors[] = sprintf(
                    $errorMessages['field_not_found'],
                    $this->translator->trans('plugin.citrix.'.$actionProduct.'.listfield')
                );
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
     * @param Events\FormBuilderEvent $event
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        $activeProducts = [];
        foreach (CitrixProducts::toArray() as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        foreach ($activeProducts as $product) {
            // Select field
            $field = [
                'label' => 'plugin.citrix.'.$product.'.listfield',
                'formType' => 'citrix_list',
                'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
                'listType' => $product,
            ];
            $event->addFormField('plugin.citrix.select.'.$product, $field);

            $validator = [
                'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.select.'.$product,
            ];
            $event->addValidator('plugin.citrix.validate.'.$product, $validator);

            // Hidden field
            $field = [
                'label' => 'plugin.citrix.'.$product.'.hiddenfield',
                'formType' => 'citrix_hidden',
                'template' => 'MauticCitrixBundle:Field:citrixhidden.html.php',
                'listType' => $product,
            ];
            $event->addFormField('plugin.citrix.hidden.'.$product, $field);

            $validator = [
                'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.hidden.'.$product,
            ];
            $event->addValidator('plugin.citrix.validate2.'.$product, $validator);

            // actions
            if (CitrixProducts::GOTOWEBINAR === $product) {
                $action = [
                    'group' => 'plugin.citrix.webinar.header',
                    'description' => 'plugin.citrix.webinar.header.tooltip',
                    'label' => 'plugin.citrix.action.subscribe.webinar',
                    'formType' => 'citrix_submit_action',
                    'template' => 'MauticFormBundle:Action:generic.html.php',
                    'eventName' => CitrixEvents::ON_WEBINAR_SUBSCRIBE_ACTION,
                ];
                $event->addSubmitAction('plugin.citrix.action.subscribe.webinar', $action);
            } else {
                if (CitrixProducts::GOTOMEETING === $product) {
                    $action = [
                        'group' => 'plugin.citrix.meeting.header',
                        'description' => 'plugin.citrix.meeting.header.tooltip',
                        'label' => 'plugin.citrix.action.start.meeting',
                        'formType' => 'citrix_submit_action',
                        'template' => 'MauticFormBundle:Action:generic.html.php',
                        'eventName' => CitrixEvents::ON_MEETING_START_ACTION,
                    ];
                    $event->addSubmitAction('plugin.citrix.action.start.meeting', $action);
                } else {
                    if (CitrixProducts::GOTOTRAINING === $product) {
                        $action = [
                            'group' => 'plugin.citrix.training.header',
                            'description' => 'plugin.citrix.training.header.tooltip',
                            'label' => 'plugin.citrix.action.subscribe.training',
                            'formType' => 'citrix_submit_action',
                            'template' => 'MauticFormBundle:Action:generic.html.php',
                            'eventName' => CitrixEvents::ON_TRAINING_SUBSCRIBE_ACTION,
                        ];
                        $event->addSubmitAction('plugin.citrix.action.subscribe.training', $action);

                        $action = [
                            'group' => 'plugin.citrix.training.header',
                            'description' => 'plugin.citrix.training.header.tooltip',
                            'label' => 'plugin.citrix.action.start.training',
                            'formType' => 'citrix_submit_action',
                            'template' => 'MauticFormBundle:Action:generic.html.php',
                            'eventName' => CitrixEvents::ON_TRAINING_START_ACTION,
                        ];
                        $event->addSubmitAction('plugin.citrix.action.start.training', $action);
                    } else {
                        if (CitrixProducts::GOTOASSIST === $product) {
                            $action = [
                                'group' => 'plugin.citrix.assist.header',
                                'description' => 'plugin.citrix.assist.header.tooltip',
                                'label' => 'plugin.citrix.action.screensharing.assist',
                                'formType' => 'citrix_submit_action',
                                'template' => 'MauticFormBundle:Action:generic.html.php',
                                'eventName' => CitrixEvents::ON_ASSIST_REMOTE_ACTION,
                            ];
                            $event->addSubmitAction('plugin.citrix.action.screensharing.assist', $action);

                            $action = [
                                'group' => 'plugin.citrix.assist.header',
                                'description' => 'plugin.citrix.assist.header.tooltip',
                                'label' => 'plugin.citrix.action.webchat.assist',
                                'formType' => 'citrix_submit_action',
                                'template' => 'MauticFormBundle:Action:generic.html.php',
                                'eventName' => CitrixEvents::ON_ASSIST_WEBCHAT_ACTION,
                            ];
                            $event->addSubmitAction('plugin.citrix.action.webchat.assist', $action);
                        }
                    }
                }
            }
        }
    }

}