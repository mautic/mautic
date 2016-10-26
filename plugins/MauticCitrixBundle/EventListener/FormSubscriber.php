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
use Symfony\Component\HttpFoundation\Response;

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
            CitrixEvents::ON_WEBINAR_REGISTER_ACTION => ['onWebinarRegister', 0],
            CitrixEvents::ON_MEETING_START_ACTION => ['onMeetingStart', 0],
            CitrixEvents::ON_TRAINING_REGISTER_ACTION => ['onTrainingRegister', 0],
            CitrixEvents::ON_TRAINING_START_ACTION => ['onTrainingStart', 0],
            CitrixEvents::ON_ASSIST_REMOTE_ACTION => ['onAssistRemote', 0],
            CitrixEvents::ON_ASSIST_WEBCHAT_ACTION => ['onAssistWebchat', 0],
            CitrixEvents::ON_FORM_VALIDATE_ACTION => ['onFormValidate', 0],
            FormEvents::FORM_PRE_SAVE => ['onFormPreSave', 0],
            // TODO: Remove onRequest event
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => ['onRequest', 0],
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE => ['onResponse', 0],
        );
    }

    /**
     * @param Events\SubmissionEvent $event
     * @param string $product
     * @throws ValidationException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function _doRegistration(Events\SubmissionEvent $event, $product)
    {
        $submission = $event->getSubmission();
        $form = $submission->getForm();
        $post = $event->getPost();
        $fields = $form->getFields();
        $actions = $form->getActions();

        $productsToRegister = self::_getProductsFromPost($actions, $fields, $post, $product);
        if (0 !== count($productsToRegister)) {
            $results = $submission->getResults();
            foreach ($productsToRegister as $productToRegister) {
                $results[$productToRegister['fieldName']] = $productToRegister['productTitle'].' ('.$productToRegister['productId'].')';
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
                    foreach ($productsToRegister as $productToRegister) {
                        $productId = $productToRegister['productId'];
                        try {
                            $isRegistered = CitrixHelper::registerToProduct(
                                $product,
                                $productId,
                                $email,
                                $firstname,
                                $lastname
                            );
                            if ($isRegistered) {
                                $eventName = CitrixHelper::getCleanString(
                                        $productToRegister['productTitle']
                                    ).'_#'.$productToRegister['productId'];
                                /** @var CitrixModel $citrixModel */
                                $citrixModel = CitrixHelper::getContainer()
                                    ->get('mautic.model.factory')
                                    ->getModel('citrix.citrix');

                                $citrixModel->addEvent(
                                    $product,
                                    $email,
                                    $eventName,
                                    CitrixEventTypes::REGISTERED
                                );
                            } else {

                            }
                        } catch (\Exception $ex) {
                            CitrixHelper::log('onProductRegistration: '.$ex->getMessage());
                            $validationException = new ValidationException($ex->getMessage());
                            $validationException->setViolations(
                                [
                                    'email' => $ex->getMessage(),
                                ]
                            );
                            throw $validationException;
                        }
                    }
                }
            }
        } else {
            $str = 'There are no '.$product.'s to register!';
            CitrixHelper::log('onProductRegistration: '. $str);
            $validationException = new ValidationException($str, 400);
            $validationException->setViolations(
                [
                    'email' => $str,
                ]
            );
            throw $validationException;
        } // end-block
    }

    public function onWebinarRegister(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOWEBINAR);
    }

    public function onMeetingStart(Events\SubmissionEvent $event)
    {
//        $form = $event->getForm();
//        $post = $event->getPost();
//        /** @var \Mautic\FormBundle\Entity\Field $field */
//        foreach ($form->getFields() as $field) {
//            if ('plugin.citrix.select.meeting' === $field->getType()) {
//                $meetingId = $post[$field->getAlias()];
//            }
//        }
    }

    public function onTrainingRegister(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOTRAINING);
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
     * @param Collection $actions
     * @param Collection $fields
     * @param array $post
     * @param string $product
     * @return array
     */
    private static function _getProductsFromPost($actions, $fields, $post, $product)
    {
        /** @var array $productlist */
        $productlist = [];

        $products = [];
        /** @var \Mautic\FormBundle\Entity\Field $field */
        foreach ($fields as $field) {
            if ('plugin.citrix.select.'.$product === $field->getType()) {
                if (0 === count($productlist)){
                    $productlist = CitrixHelper::getCitrixChoices($product);
                }
                $alias = $field->getAlias();
                /** @var array $productIds */
                $productIds = $post[$alias];
                if (!(array)$productIds) {
                    $productIds = [$productIds];
                }
                foreach ($productIds as $productId) {
                    $products[] = array(
                        'fieldName' => $alias,
                        'productId' => $productId,
                        'productTitle' => array_key_exists(
                            $productId,
                            $productlist
                        ) ? $productlist[$productId] : 'untitled',
                    );
                }
            }
        }

        // check if there are products in the actions
        /** @var Action $action */
        foreach ($actions as $action) {
            if (0 === strpos($action->getType(), 'plugin.citrix.action')) {
                if (0 === count($productlist)){
                    $productlist = CitrixHelper::getCitrixChoices($product);
                }
                $actionProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $action->getType());
                if (!CitrixHelper::isAuthorized('Goto'.$actionProduct)) {
                    continue;
                }
                $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                $productId = $action->getProperties()['product'];
                if (array_key_exists(
                    $productId,
                    $productlist)) {
                    $products[] = array(
                        'fieldName' => $actionAction,
                        'productId' => $productId,
                        'productTitle' => $productlist[$productId],
                    );
                }
            }
        }

        return $products;
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
     * @param Form $form
     * @return array
     * @throws \InvalidArgumentException
     */
    private function _checkFormValidity(Form $form)
    {
        $errors = [];
        $actions = $form->getActions();
        $fields = $form->getFields();

        if (null !== $actions && null !== $fields) {

            $actionFields = [
                'register.webinar' => ['email', 'firstname', 'lastname'],
                'register.training' => ['email', 'firstname', 'lastname'],
                'start.meeting' => ['email'],
                'start.training' => ['email'],
                'screensharing.assist' => ['email', 'firstname', 'lastname'],
            ];

            $errorMessages = [
                'lead_field_not_found' => $this->translator->trans(
                    'plugin.citrix.formaction.validator.leadfieldnotfound'
                ),
                'field_not_found' => $this->translator->trans('plugin.citrix.formaction.validator.fieldnotfound'),
                'field_should_be_required' => $this->translator->trans(
                    'plugin.citrix.formaction.validator.fieldshouldberequired'
                ),
            ];

            /** @var Action $action */
            foreach ($actions as $action) {
                if (0 === strpos($action->getType(), 'plugin.citrix.action')) {
                    $actionProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $action->getType());
                    if (!CitrixHelper::isAuthorized('Goto'.$actionProduct)) {
                        continue;
                    }
                    $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());

                    // get lead fields
                    $currentLeadFields = [];
                    foreach ($fields as $field) {
                        $leadField = $field->getLeadField();
                        if ('' !== $leadField) {
                            $currentLeadFields[$leadField] = $field->getIsRequired();
                        }
                    }

                    $props = $action->getProperties();
                    if (array_key_exists('product', $props) && 'form' === $props['product']) {
                        // the product will be selected from a list in the form
                        // search for the select field and perform validation for a corresponding action

                        $hasCitrixListField = false;
                        /** @var Field $field */
                        foreach ($fields as $field) {
                            $fieldProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $field->getType());
                            if ($fieldProduct === $actionProduct) {
                                $hasCitrixListField = true;
                                if (!$field->getIsRequired()) {
                                    $errors[] = sprintf(
                                        $errorMessages['field_should_be_required'],
                                        $this->translator->trans('plugin.citrix.'.$fieldProduct.'.listfield')
                                    );
                                }
                            }
                        } // foreach $fields

                        if (!$hasCitrixListField) {
                            $errors[] = sprintf(
                                $errorMessages['field_not_found'],
                                $this->translator->trans('plugin.citrix.'.$actionProduct.'.listfield')
                            );
                        }
                    }

                    // check that the corresponding fields for the values in the form exist
                    /** @var array $mandatoryActionFields */
                    $mandatoryActionFields = $actionFields[$actionAction];
                    foreach ($mandatoryActionFields as $actionField) {
                        /** @var Field $field */
                        $field = $fields->get($props[$actionField]);
                        if (null === $field) {
                            $errors[] = sprintf($errorMessages['lead_field_not_found'], $actionField);
                            break;
                        } else {
                            if (!$field->getIsRequired()) {
                                $errors[] = sprintf($errorMessages['field_should_be_required'], $actionField);
                                break;
                            }
                        }
                    }

                    // check for lead fields
                    /** @var array $mandatoryFields */
                    $mandatoryFields = $actionFields[$actionAction];
                    foreach ($mandatoryFields as $mandatoryField) {
                        if (!array_key_exists($mandatoryField, $currentLeadFields)) {
                            $errors[] = sprintf($errorMessages['lead_field_not_found'], $mandatoryField);
                        } else {
                            if (!$currentLeadFields[$mandatoryField]) {
                                $errors[] = sprintf(
                                    $errorMessages['field_should_be_required'],
                                    $mandatoryField
                                );
                            }
                        }
                    }


                } // end-if there is a Citrix action
            } // foreach $actions
        }

        return $errors;
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

            // actions
            if (CitrixProducts::GOTOWEBINAR === $product) {
                $action = [
                    'group' => 'plugin.citrix.form.header',
                    'description' => 'plugin.citrix.form.header.webinar',
                    'label' => 'plugin.citrix.action.register.webinar',
                    'formType' => 'citrix_submit_action',
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product' => $product,
                            'data-product-action' => 'register',
                        ],
                    ],
                    'template' => 'MauticFormBundle:Action:generic.html.php',
                    'eventName' => CitrixEvents::ON_WEBINAR_REGISTER_ACTION,
                ];
                $event->addSubmitAction('plugin.citrix.action.register.webinar', $action);
            } else {
                if (CitrixProducts::GOTOMEETING === $product) {
                    $action = [
                        'group' => 'plugin.citrix.form.header',
                        'description' => 'plugin.citrix.form.header.meeting',
                        'label' => 'plugin.citrix.action.start.meeting',
                        'formType' => 'citrix_submit_action',
                        'template' => 'MauticFormBundle:Action:generic.html.php',
                        'eventName' => CitrixEvents::ON_MEETING_START_ACTION,
                        'formTypeOptions' => [
                            'attr' => [
                                'data-product' => $product,
                                'data-product-action' => 'start',
                            ],
                        ],
                    ];
                    $event->addSubmitAction('plugin.citrix.action.start.meeting', $action);
                } else {
                    if (CitrixProducts::GOTOTRAINING === $product) {
                        $action = [
                            'group' => 'plugin.citrix.form.header',
                            'description' => 'plugin.citrix.form.header.training',
                            'label' => 'plugin.citrix.action.register.training',
                            'formType' => 'citrix_submit_action',
                            'template' => 'MauticFormBundle:Action:generic.html.php',
                            'eventName' => CitrixEvents::ON_TRAINING_REGISTER_ACTION,
                            'formTypeOptions' => [
                                'attr' => [
                                    'data-product' => $product,
                                    'data-product-action' => 'register',
                                ],
                            ],
                        ];
                        $event->addSubmitAction('plugin.citrix.action.register.training', $action);

                        $action = [
                            'group' => 'plugin.citrix.form.header',
                            'description' => 'plugin.citrix.form.header.start.training',
                            'label' => 'plugin.citrix.action.start.training',
                            'formType' => 'citrix_submit_action',
                            'template' => 'MauticFormBundle:Action:generic.html.php',
                            'eventName' => CitrixEvents::ON_TRAINING_START_ACTION,
                            'formTypeOptions' => [
                                'attr' => [
                                    'data-product' => $product,
                                    'data-product-action' => 'start',
                                ],
                            ],
                        ];
                        $event->addSubmitAction('plugin.citrix.action.start.training', $action);
                    } else {
                        if (CitrixProducts::GOTOASSIST === $product) {
                            $action = [
                                'group' => 'plugin.citrix.form.header',
                                'description' => 'plugin.citrix.form.header.assist',
                                'label' => 'plugin.citrix.action.screensharing.assist',
                                'formType' => 'citrix_submit_action',
                                'template' => 'MauticFormBundle:Action:generic.html.php',
                                'eventName' => CitrixEvents::ON_ASSIST_REMOTE_ACTION,
                                'formTypeOptions' => [
                                    'attr' => [
                                        'data-product' => $product,
                                        'data-product-action' => 'screensharing',
                                    ],
                                ],
                            ];
                            $event->addSubmitAction('plugin.citrix.action.screensharing.assist', $action);
                        }
                    }
                }
            }
        }
    }

}