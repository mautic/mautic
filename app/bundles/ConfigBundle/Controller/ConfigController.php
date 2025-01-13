<?php

namespace Mautic\ConfigBundle\Controller;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\Mapper\ConfigMapper;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConfigController extends FormController
{
    /**
     * Controller action for editing the application configuration.
     *
     * @return JsonResponse|Response
     */
    public function editAction(Request $request, BundleHelper $bundleHelper, Configurator $configurator, CacheHelper $cacheHelper, PathsHelper $pathsHelper, ConfigMapper $configMapper, TokenStorageInterface $tokenStorage)
    {
        // admin only allowed
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        $event      = new ConfigBuilderEvent($bundleHelper);
        $dispatcher = $this->dispatcher;
        $dispatcher->dispatch($event, ConfigEvents::CONFIG_ON_GENERATE);
        $fileFields = $event->getFileFields();
        $formThemes = $event->getFormThemes();

        $formConfigs = $configMapper->bindFormConfigsWithRealValues($event->getForms());

        $this->mergeParamsWithLocal($formConfigs, $pathsHelper);

        // Create the form
        $action = $this->generateUrl('mautic_config_action', ['objectAction' => 'edit']);
        $form   = $this->formFactory->create(
            ConfigType::class,
            $formConfigs,
            [
                'action'     => $action,
                'fileFields' => $fileFields,
            ]
        );

        $originalNormData = $form->getNormData();

        $isWritable = $configurator->isFileWritable();
        $openTab    = null;

        // Check for a submitted form and process it
        if ('POST' == $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                $isValid = false;
                if ($isWritable && $isValid = $this->isFormValid($form)) {
                    // Bind request to the form
                    $post     = $request->request;

                    /** @var mixed[] $formData */
                    $formData = $form->getData();

                    // Dispatch pre-save event. Bundles may need to modify some field values like passwords before save
                    $configEvent = new ConfigEvent($formData, $post);
                    $configEvent
                        ->setOriginalNormData($originalNormData)
                        ->setNormData($form->getNormData());
                    $dispatcher->dispatch($configEvent, ConfigEvents::CONFIG_PRE_SAVE);
                    $formValues = $configEvent->getConfig();

                    $errors      = $configEvent->getErrors();
                    $fieldErrors = $configEvent->getFieldErrors();

                    if ($errors || $fieldErrors) {
                        foreach ($errors as $message => $messageVars) {
                            $form->addError(
                                new FormError($this->translator->trans($message, $messageVars, 'validators'))
                            );
                        }

                        foreach ($fieldErrors as $key => $fields) {
                            foreach ($fields as $field => $fieldError) {
                                $form[$key][$field]->addError(
                                    new FormError($this->translator->trans($fieldError[0], $fieldError[1], 'validators'))
                                );
                            }
                        }
                        $isValid = false;
                    } else {
                        // Prevent these from getting overwritten with empty values
                        $unsetIfEmpty = $configEvent->getPreservedFields();
                        $unsetIfEmpty = array_merge($unsetIfEmpty, $fileFields);

                        // Merge each bundle's updated configuration into the local configuration
                        foreach ($formValues as $object) {
                            $checkThese = array_intersect(array_keys($object), $unsetIfEmpty);
                            foreach ($checkThese as $checkMe) {
                                if (empty($object[$checkMe])) {
                                    unset($object[$checkMe]);
                                }
                            }

                            $configurator->mergeParameters($object);
                        }

                        try {
                            // Ensure the config has a secret key
                            $params = $configurator->getParameters();
                            if (empty($params['secret_key'])) {
                                $configurator->mergeParameters(['secret_key' => EncryptionHelper::generateKey()]);
                            }

                            $configurator->write();
                            $dispatcher->dispatch($configEvent, ConfigEvents::CONFIG_POST_SAVE);

                            $this->addFlashMessage('mautic.config.config.notice.updated');

                            $cacheHelper->refreshConfig();

                            if (!empty($formData['coreconfig']['last_shown_tab'])) {
                                $openTab = $formData['coreconfig']['last_shown_tab'];
                            }
                        } catch (\RuntimeException $exception) {
                            $this->addFlashMessage('mautic.config.config.error.not.updated', ['%exception%' => $exception->getMessage()], 'error');
                        }

                        $this->setLocale($request, $tokenStorage, $params);
                    }
                } elseif (!$isWritable) {
                    $form->addError(
                        new FormError(
                            $this->translator->trans('mautic.config.notwritable')
                        )
                    );
                }
            }

            // If the form is saved or cancelled, redirect back to the dashboard
            if ($cancelled || $isValid) {
                if (!$cancelled && $this->isFormApplied($form)) {
                    $redirectParameters = ['objectAction' => 'edit'];
                    if ($openTab) {
                        $redirectParameters['tab'] = $openTab;
                    }

                    return $this->delegateRedirect($this->generateUrl('mautic_config_action', $redirectParameters));
                } else {
                    return $this->delegateRedirect($this->generateUrl('mautic_dashboard_index'));
                }
            }
        }

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'        => $tmpl,
                    'security'    => $this->security,
                    'form'        => $form->createView(),
                    'formThemes'  => $formThemes,
                    'formConfigs' => $formConfigs,
                    'isWritable'  => $isWritable,
                ],
                'contentTemplate' => '@MauticConfig/Config/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_config_index',
                    'mauticContent' => 'config',
                    'route'         => $this->generateUrl('mautic_config_action', ['objectAction' => 'edit']),
                ],
            ]
        );
    }

    /**
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function downloadAction(Request $request, BundleHelper $bundleHelper, $objectId)
    {
        // admin only allowed
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        $event      = new ConfigBuilderEvent($bundleHelper);
        $dispatcher = $this->dispatcher;
        $dispatcher->dispatch($event, ConfigEvents::CONFIG_ON_GENERATE);

        // Extract and base64 encode file contents
        $fileFields = $event->getFileFields();

        if (!in_array($objectId, $fileFields)) {
            return $this->accessDenied();
        }

        $content  = $this->coreParametersHelper->get($objectId);
        $filename = $request->get('filename', $objectId);

        if ($decoded = base64_decode($content)) {
            $response = new Response($decoded);
            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename);
            $response->headers->set('Expires', '0');
            $response->headers->set('Cache-Control', 'must-revalidate');
            $response->headers->set('Pragma', 'public');

            return $response;
        }

        return $this->notFound();
    }

    /**
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function removeAction(BundleHelper $bundleHelper, Configurator $configurator, CacheHelper $cacheHelper, $objectId)
    {
        // admin only allowed
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        $success    = 0;
        $event      = new ConfigBuilderEvent($bundleHelper);
        $dispatcher = $this->dispatcher;
        $dispatcher->dispatch($event, ConfigEvents::CONFIG_ON_GENERATE);

        // Extract and base64 encode file contents
        $fileFields = $event->getFileFields();

        if (in_array($objectId, $fileFields)) {
            $configurator->mergeParameters([$objectId => null]);
            try {
                $configurator->write();

                $cacheHelper->refreshConfig();
                $success = 1;
            } catch (\Exception) {
            }
        }

        return new JsonResponse(['success' => $success]);
    }

    /**
     * Merges default parameters from each subscribed bundle with the local (real) params.
     */
    private function mergeParamsWithLocal(array &$forms, PathsHelper $pathsHelper): void
    {
        $doNotChange     = $this->coreParametersHelper->get('mautic.security.restrictedConfigFields');
        $localConfigFile = $pathsHelper->getLocalConfigurationFile();

        // Import the current local configuration, $parameters is defined in this file

        $parameters = [];
        include $localConfigFile;

        /** @var mixed[] $parameters */
        $localParams = $parameters;

        foreach ($forms as &$form) {
            // Merge the bundle params with the local params
            foreach ($form['parameters'] as $key => $value) {
                if (in_array($key, $doNotChange)) {
                    unset($form['parameters'][$key]);
                } elseif (array_key_exists($key, $localParams)) {// @phpstan-ignore function.impossibleType (Not sure what this is about)
                    $paramValue               = $localParams[$key];
                    $form['parameters'][$key] = $paramValue;
                }
            }
        }
    }

    /**
     * @param array<string, string> $params
     */
    private function setLocale(Request $request, TokenStorageInterface $tokenStorage, array $params): void
    {
        $me = $tokenStorage->getToken()->getUser();
        assert($me instanceof User);
        $locale = $me->getLocale();

        if (empty($locale)) {
            $locale = $params['locale'] ?? $this->coreParametersHelper->get('locale');
        }

        $request->getSession()->set('_locale', $locale);
    }
}
