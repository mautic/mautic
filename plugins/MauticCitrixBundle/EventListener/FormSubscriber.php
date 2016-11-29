<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    use CitrixRegistrationTrait;
    use CitrixStartTrait;

    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * @var SubmissionModel
     */
    protected $submissionModel;

    /**
     * @var CitrixModel
     */
    protected $citrixModel;

    /**
     * FormSubscriber constructor.
     *
     * @param CitrixModel     $citrixModel
     * @param FormModel       $formModel
     * @param SubmissionModel $submissionModel
     */
    public function __construct(CitrixModel $citrixModel, FormModel $formModel, SubmissionModel $submissionModel)
    {
        $this->citrixModel     = $citrixModel;
        $this->formModel       = $formModel;
        $this->submissionModel = $submissionModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                                         => ['onFormBuilder', 0],
            CitrixEvents::ON_WEBINAR_REGISTER_ACTION                          => ['onWebinarRegister', 0],
            CitrixEvents::ON_MEETING_START_ACTION                             => ['onMeetingStart', 0],
            CitrixEvents::ON_TRAINING_REGISTER_ACTION                         => ['onTrainingRegister', 0],
            CitrixEvents::ON_TRAINING_START_ACTION                            => ['onTrainingStart', 0],
            CitrixEvents::ON_ASSIST_REMOTE_ACTION                             => ['onAssistRemote', 0],
            CitrixEvents::ON_FORM_VALIDATE_ACTION                             => ['onFormValidate', 0],
            FormEvents::FORM_PRE_SAVE                                         => ['onFormPreSave', 0],
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST  => ['onRequest', 0],
            \Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE => ['onResponse', 0],
        ];
    }

    /**
     * @param Events\SubmissionEvent $event
     * @param string                 $product
     * @param string                 $startType indicates that this is a start product, not registration
     *
     * @throws ValidationException
     */
    private function _doRegistration(Events\SubmissionEvent $event, $product, $startType = null)
    {
        $submission = $event->getSubmission();
        $form       = $submission->getForm();
        $post       = $event->getPost();
        $fields     = $form->getFields();
        $actions    = $form->getActions();

        try {
            // gotoassist screen sharing does not need a product
            if ('assist' !== $product) {
                // check if there are products in the actions
                /** @var Action $action */
                foreach ($actions as $action) {
                    if (0 === strpos($action->getType(), 'plugin.citrix.action')) {
                        $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                        $actionAction = str_replace('.', '_', $actionAction);
                        if (!array_key_exists($actionAction, $submission->getResults())) {
                            // add new hidden field to store the product id
                            $field = new Field();
                            $field->setType('hidden');
                            $field->setLabel(ucfirst($product).' ID');
                            $field->setAlias($actionAction);
                            $field->setForm($form);
                            $field->setOrder(99999);
                            $field->setSaveResult(true);
                            $form->addField($actionAction, $field);
                            $this->em->persist($form);
                            /* @var FormModel $formModel */
                            $this->formModel->createTableSchema($form);
                        }
                    }
                }
            }

            $productsToRegister = self::_getProductsFromPost($actions, $fields, $post, $product);
            if ($product === 'assist' || (0 !== count($productsToRegister))) {
                $results = $submission->getResults();

                // persist the new values
                if ($product !== 'assist') {
                    // replace the submitted value with something more legible
                    foreach ($productsToRegister as $productToRegister) {
                        $results[$productToRegister['fieldName']] = $productToRegister['productTitle'].' ('.$productToRegister['productId'].')';
                    }

                    /** @var SubmissionRepository $repo */
                    $repo             = $this->submissionModel->getRepository();
                    $resultsTableName = $repo->getResultsTableName($form->getId(), $form->getAlias());
                    $tableKeys        = ['submission_id' => $submission->getId()];
                    $this->em
                        ->getConnection()
                        ->update($resultsTableName, $results, $tableKeys);
                } else {
                    // dummy field for assist
                    $productsToRegister[] = // needed because there are no ids
                        [
                            'fieldName'    => $startType,
                            'productId'    => $startType,
                            'productTitle' => $startType,
                        ];
                }

                /** @var Lead $currentLead */
                $currentLead = $event->getLead();

                // execute action
                if ($currentLead instanceof Lead) {
                    if (null !== $startType) {
                        /** @var Action $action */
                        foreach ($actions as $action) {
                            $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                            if ($actionAction === $startType) {
                                if (array_key_exists('template', $action->getProperties())) {
                                    $emailId = $action->getProperties()['template'];
                                    $this->startProduct(
                                        $product,
                                        $currentLead,
                                        $productsToRegister,
                                        $emailId,
                                        $action->getId()
                                    );
                                } else {
                                    throw new BadRequestHttpException('Email template not found!');
                                }
                            }
                        }
                    } else {
                        $this->registerProduct($product, $currentLead, $productsToRegister);
                    }
                } else {
                    throw new BadRequestHttpException('Lead not found!');
                }
            } else {
                throw new BadRequestHttpException(
                    'There are no products to '.((null === $startType) ? 'register' : 'start')
                );
            } // end-block
        } catch (\Exception $ex) {
            CitrixHelper::log('onProductRegistration - '.$product.': '.$ex->getMessage());
            $validationException = new ValidationException($ex->getMessage());
            $validationException->setViolations(
                [
                    'email' => $ex->getMessage(),
                ]
            );
            throw $validationException;
        }
    }

    public function onWebinarRegister(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOWEBINAR);
    }

    public function onMeetingStart(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOMEETING, 'start.meeting');
    }

    public function onTrainingRegister(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOTRAINING);
    }

    public function onTrainingStart(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOTRAINING, 'start.training');
    }

    public function onAssistRemote(Events\SubmissionEvent $event)
    {
        $this->_doRegistration($event, CitrixProducts::GOTOASSIST, 'screensharing.assist');
    }

    /**
     * Helper function to debug REST responses.
     *
     * @param PluginIntegrationRequestEvent $event
     */
    public function onResponse(PluginIntegrationRequestEvent $event)
    {
        //        /** @var Response $response */
//        $response = $event->getResponse();
//        CitrixHelper::log(
//            PHP_EOL. //$response->getStatusCode() . ' ' .
//            print_r($response, true)
//        );
    }

    /**
     * Helper function to debug REST requests.
     *
     * @param PluginIntegrationRequestEvent $event
     */
    public function onRequest(PluginIntegrationRequestEvent $event)
    {
        //        CitrixHelper::log(
//            PHP_EOL.$event->getMethod().' '.$event->getUrl().' '.
//            var_export($event->getHeaders(), true).
//            var_export($event->getParameters(), true)
//        );

        // clean parameter that was breaking the call
        if (preg_match('/\/G2W\/rest\//', $event->getUrl())) {
            $params = $event->getParameters();
            unset($params['access_token']);
            $event->setParameters($params);
        }
    }

    /**
     * @param Events\ValidationEvent $event
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $field        = $event->getField();
        $eventType    = preg_filter('/^plugin\.citrix\.select\.(.*)$/', '$1', $field->getType());
        $doValidation = CitrixHelper::isAuthorized('Goto'.$eventType);

        if ($doValidation) {
            $list = CitrixHelper::getCitrixChoices($eventType);
            /** @var array $values */
            $values = $event->getValue();

            if (!is_array($values) && !is_object($values)) {
                $values = [$values];
            }

            if (is_array($values) || is_object($values)) {
                foreach ($values as $value) {
                    if (!array_key_exists($value, $list)) {
                        $event->failedValidation(
                            $value.': '.$this->translator->trans('plugin.citrix.'.$eventType.'.nolongeravailable')
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Collection $actions
     * @param Collection $fields
     * @param array      $post
     * @param string     $product
     *
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
                if (0 === count($productlist)) {
                    $productlist = CitrixHelper::getCitrixChoices($product);
                }
                $alias = $field->getAlias();
                /** @var array $productIds */
                $productIds = $post[$alias];
                if (!is_array($productIds) && !is_object($productIds)) {
                    $productIds = [$productIds];
                }
                if (is_array($productIds) || is_object($productIds)) {
                    foreach ($productIds as $productId) {
                        $products[] = [
                            'fieldName'    => $alias,
                            'productId'    => $productId,
                            'productTitle' => array_key_exists(
                                $productId,
                                $productlist
                            ) ? $productlist[$productId] : 'untitled',
                        ];
                    }
                }
            }
        }

        // gotoassist screen sharing does not need a product
        if ('assist' !== $product) {
            // check if there are products in the actions
            /** @var Action $action */
            foreach ($actions as $action) {
                if (0 === strpos($action->getType(), 'plugin.citrix.action')) {
                    if (0 === count($productlist)) {
                        $productlist = CitrixHelper::getCitrixChoices($product);
                    }
                    $actionProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $action->getType());
                    if (!CitrixHelper::isAuthorized('Goto'.$actionProduct)) {
                        continue;
                    }
                    $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                    $productId    = $action->getProperties()['product'];
                    if (array_key_exists(
                        $productId,
                        $productlist
                    )) {
                        $products[] = [
                            'fieldName'    => str_replace('.', '_', $actionAction),
                            'productId'    => $productId,
                            'productTitle' => $productlist[$productId],
                        ];
                    }
                }
            }
        }

        return $products;
    }

    /**
     * @param Events\FormEvent $event
     *
     * @throws ValidationException
     */
    public function onFormPreSave(Events\FormEvent $event)
    {
        $form   = $event->getForm();
        $fields = $form->getFields()->getValues();

        // Verify if the form is well configured
        if (0 !== count($fields)) {
            $violations = $this->_checkFormValidity($form);
            if (0 !== count($violations)) {
                $event->stopPropagation();
                $error     = implode('<br/>', $violations);
                $exception = (new ValidationException($error))
                    ->setViolations($violations);
                throw $exception;
            }
        }
    }

    /**
     * @param Form $form
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function _checkFormValidity(Form $form)
    {
        $errors  = [];
        $actions = $form->getActions();
        $fields  = $form->getFields();

        if (null !== $actions && null !== $fields) {
            $actionFields = [
                'register.webinar'     => ['email', 'firstname', 'lastname'],
                'register.training'    => ['email', 'firstname', 'lastname'],
                'start.meeting'        => ['email'],
                'start.training'       => ['email'],
                'screensharing.assist' => ['email', 'firstname', 'lastname'],
            ];

            $errorMessages = [
                'lead_field_not_found' => $this->translator->trans(
                    'plugin.citrix.formaction.validator.leadfieldnotfound'
                ),
                'field_not_found'          => $this->translator->trans('plugin.citrix.formaction.validator.fieldnotfound'),
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
                                    $errors[$fieldProduct.'required'] = sprintf(
                                        $errorMessages['field_should_be_required'],
                                        $this->translator->trans('plugin.citrix.'.$fieldProduct.'.listfield')
                                    );
                                }
                            }
                        } // foreach $fields

                        if (!$hasCitrixListField) {
                            $errors[$actionProduct.'listfield'] = sprintf(
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
                            $errors[$actionField.'notfound'] = sprintf($errorMessages['lead_field_not_found'], $actionField);
                            break;
                        } else {
                            if (!$field->getIsRequired()) {
                                $errors[$actionField.'required'] = sprintf($errorMessages['field_should_be_required'], $actionField);
                                break;
                            }
                        }
                    }

                    // check for lead fields
                    /** @var array $mandatoryFields */
                    $mandatoryFields = $actionFields[$actionAction];
                    foreach ($mandatoryFields as $mandatoryField) {
                        if (!array_key_exists($mandatoryField, $currentLeadFields)) {
                            $errors[$mandatoryField.'notfound'] = sprintf($errorMessages['lead_field_not_found'], $mandatoryField);
                        } else {
                            if (!$currentLeadFields[$mandatoryField]) {
                                $errors[$mandatoryField.'required'] = sprintf(
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
     * @param Events\FormBuilderEvent $event
     *
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
                'label'    => 'plugin.citrix.'.$product.'.listfield',
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
                    'group'           => 'plugin.citrix.form.header',
                    'description'     => 'plugin.citrix.form.header.webinar',
                    'label'           => 'plugin.citrix.action.register.webinar',
                    'formType'        => 'citrix_submit_action',
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product'        => $product,
                            'data-product-action' => 'register',
                        ],
                    ],
                    'template'  => 'MauticFormBundle:Action:generic.html.php',
                    'eventName' => CitrixEvents::ON_WEBINAR_REGISTER_ACTION,
                ];
                $event->addSubmitAction('plugin.citrix.action.register.webinar', $action);
            } else {
                if (CitrixProducts::GOTOMEETING === $product) {
                    $action = [
                        'group'           => 'plugin.citrix.form.header',
                        'description'     => 'plugin.citrix.form.header.meeting',
                        'label'           => 'plugin.citrix.action.start.meeting',
                        'formType'        => 'citrix_submit_action',
                        'template'        => 'MauticFormBundle:Action:generic.html.php',
                        'eventName'       => CitrixEvents::ON_MEETING_START_ACTION,
                        'formTypeOptions' => [
                            'attr' => [
                                'data-product'        => $product,
                                'data-product-action' => 'start',
                            ],
                        ],
                    ];
                    $event->addSubmitAction('plugin.citrix.action.start.meeting', $action);
                } else {
                    if (CitrixProducts::GOTOTRAINING === $product) {
                        $action = [
                            'group'           => 'plugin.citrix.form.header',
                            'description'     => 'plugin.citrix.form.header.training',
                            'label'           => 'plugin.citrix.action.register.training',
                            'formType'        => 'citrix_submit_action',
                            'template'        => 'MauticFormBundle:Action:generic.html.php',
                            'eventName'       => CitrixEvents::ON_TRAINING_REGISTER_ACTION,
                            'formTypeOptions' => [
                                'attr' => [
                                    'data-product'        => $product,
                                    'data-product-action' => 'register',
                                ],
                            ],
                        ];
                        $event->addSubmitAction('plugin.citrix.action.register.training', $action);

                        $action = [
                            'group'           => 'plugin.citrix.form.header',
                            'description'     => 'plugin.citrix.form.header.start.training',
                            'label'           => 'plugin.citrix.action.start.training',
                            'formType'        => 'citrix_submit_action',
                            'template'        => 'MauticFormBundle:Action:generic.html.php',
                            'eventName'       => CitrixEvents::ON_TRAINING_START_ACTION,
                            'formTypeOptions' => [
                                'attr' => [
                                    'data-product'        => $product,
                                    'data-product-action' => 'start',
                                ],
                            ],
                        ];
                        $event->addSubmitAction('plugin.citrix.action.start.training', $action);
                    } else {
                        if (CitrixProducts::GOTOASSIST === $product) {
                            $action = [
                                'group'           => 'plugin.citrix.form.header',
                                'description'     => 'plugin.citrix.form.header.assist',
                                'label'           => 'plugin.citrix.action.screensharing.assist',
                                'formType'        => 'citrix_submit_action',
                                'template'        => 'MauticFormBundle:Action:generic.html.php',
                                'eventName'       => CitrixEvents::ON_ASSIST_REMOTE_ACTION,
                                'formTypeOptions' => [
                                    'attr' => [
                                        'data-product'        => $product,
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
