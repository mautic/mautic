<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Form\Type\IntegrationConfigType;
use MauticPlugin\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Integration\BasicIntegration;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigController extends AbstractFormController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Form
     */
    private $form;

    /**
     * @var BasicIntegration
     */
    private $integrationObject;

    /**
     * @var Integration
     */
    private $integrationConfiguration;

    /**
     * @param string  $integration
     * @param Request $request
     * @param int     $page
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction(string $integration, Request $request, $page = 1)
    {
        // Check ACL
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        // Find the integration
        /** @var ConfigIntegrationsHelper $integrationsHelper */
        $integrationsHelper = $this->get('mautic.integrations.helper.config_integrations');
        try {
            $this->integrationObject        = $integrationsHelper->getIntegration($integration);
            $this->integrationConfiguration = $this->integrationObject->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException $exception) {
            return $this->notFound();
        }

        // Set the request for private methods
        $this->request = $request;

        // Create the form
        $this->form = $this->get('form.factory')->create(
            IntegrationConfigType::class,
            $this->integrationConfiguration,
            [
                'action'      => $this->generateUrl('mautic_integration_config', ['integration' => $integration, 'page' => $page]),
                'integration' => $integration,
            ]
        );

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->submitForm();
        }

        return $this->showForm();
    }

    /**
     * @return JsonResponse|Response
     */
    private function submitForm()
    {
        if ($cancelled = $this->isFormCancelled($this->form)) {
            return $this->closeForm();
        }

        // Submit the form
        $this->form->handleRequest($this->request);

        // Show the form if validation failed or the appy button as clicked
        if (!$this->form->isValid() || $this->isFormApplied($this->form)) {
            return $this->showForm();
        }

        return $this->closeForm();
    }

    /**
     * @return JsonResponse|Response
     */
    private function showForm()
    {
        return $this->delegateView(
            [
                'viewParameters'  => [
                    'integrationObject' => $this->integrationObject,
                    'form'              => $this->form->createView(),
                    'activeTab'         => $this->request->get('activeTab'),
                ],
                'contentTemplate' => 'IntegrationsBundle:Config:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integrationConfig',
                    'route'         => false,
                ],
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    private function closeForm()
    {
        return new JsonResponse(
            [
                'closeForm'     => 1,
                'enabled'       => $this->integrationConfiguration->getIsPublished(),
                'name'          => $this->integrationConfiguration->getName(),
                'mauticContent' => 'integrationConfig',
            ]
        );
    }
}
