<?php

namespace Mautic\InstallBundle\Controller;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class InstallController extends CommonController
{
    /** @var Configurator */
    private $configurator;

    /** @var InstallService */
    private $installer;

    /**
     * Initialize controller.
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->configurator = $this->container->get('mautic.configurator');
        $this->installer    = $this->container->get('mautic.install.service');
    }

    /**
     * Controller action for install steps.
     *
     * @param int $index The step number to process
     *
     * @return JsonResponse|Response
     *
     * @throws DBALException
     */
    public function stepAction(float $index = 0)
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app
        // is installed and redirect
        if ($this->installer->checkIfInstalled()) {
            return $this->redirect($this->generateUrl('mautic_dashboard_index'));
        }

        if ($index - floor($index) > 0) {
            $subIndex = (int) (round($index - floor($index), 1) * 10);
            $index    = floor($index);
        }
        $index = (int) $index;

        $params = $this->configurator->getParameters();

        $session        = $this->get('session');
        $completedSteps = $session->get('mautic.installer.completedsteps', []);

        // Check to ensure the installer is in the right place
        if ((empty($params) || empty($params['db_driver'])) && $index > 1) {
            $session->set('mautic.installer.completedsteps', [0]);

            return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1]));
        }

        /** @var \Mautic\CoreBundle\Configurator\Step\StepInterface $step */
        $step   = $this->configurator->getStep($index)[0];
        $action = $this->generateUrl('mautic_installer_step', ['index' => $index]);

        $form = $this->createForm($step->getFormType(), $step, ['action' => $action]);
        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        // Note if this step is complete
        $complete = false;

        if ('POST' === $this->request->getMethod()) {
            $form->handleRequest($this->request);
            if ($form->isValid()) {
                // Post-step processing
                $formData = $form->getData();

                switch ($index) {
                    case InstallService::CHECK_STEP:
                        $complete = true;

                        break;
                    case InstallService::DOCTRINE_STEP:
                        // password field does not retain configured defaults
                        if (empty($formData->password) && !empty($params['db_password'])) {
                            $formData->password = $params['db_password'];
                        }
                        $dbParams = (array) $formData;
                        $messages = $this->installer->createDatabaseStep($step, $dbParams);
                        if (!empty($messages)) {
                            $this->handleInstallerErrors($form, $messages);
                            break;
                        }

                        /** @var \Doctrine\ORM\EntityManager */
                        $entityManager = $this->get('doctrine.orm.default_entity_manager');

                        /**
                         * We need to clear the ORM metadata cache before creating the schema. If the user provided a database
                         * table prefix in the UI installer, cached table names don't have the prefix yet (e.g. oauth2_clients).
                         * After clearing the metadata cache, Doctrine automatically recreates it with the correct prefixes (e.g.
                         * mau_oauth2_clients), if applicable.
                         */
                        $entityManager->getConfiguration()->getMetadataCache()->clear();

                        // Refresh to install schema with new connection information in the container
                        return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1.1]));
                    case InstallService::USER_STEP:
                        $adminParam = (array) $formData;
                        $messages   = $this->installer->createAdminUserStep($adminParam);

                        if (!empty($messages)) {
                            $this->handleInstallerErrors($form, $messages);
                            break;
                        }

                        // Store the data to repopulate the form
                        unset($formData->password);
                        $session->set('mautic.installer.user', $formData);

                        $complete = true;
                        break;

                    case InstallService::EMAIL_STEP:
                        $emailParam = (array) $formData;
                        $messages   = $this->installer->setupEmailStep($step, $emailParam);

                        if (!empty($messages)) {
                            $this->handleInstallerErrors($form, $messages);
                            break;
                        }

                        $complete = true;
                        break;
                }
            }
        } elseif (!empty($subIndex)) {
            switch ($index) {
                case InstallService::DOCTRINE_STEP:
                    $dbParams = (array) $step;

                    switch ($subIndex) {
                        case 1:
                            $messages = $this->installer->createSchemaStep($dbParams);
                            if (!empty($messages)) {
                                $this->handleInstallerErrors($form, $messages);

                                return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1]));
                            }

                            return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1.2]));
                        case 2:
                            $messages = $this->installer->createFixturesStep($this->container);
                            if (!empty($messages)) {
                                $this->handleInstallerErrors($form, $messages);

                                return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1]));
                            }

                            $complete = true;
                            break;
                    }
                    break;
            }
        }

        if ($complete) {
            $completedSteps[] = $index;
            $session->set('mautic.installer.completedsteps', $completedSteps);
            ++$index;

            if ($index < $this->configurator->getStepCount()) {
                // On to the next step

                return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => (int) $index]));
            } else {
                $siteUrl  = $this->request->getSchemeAndHttpHost().$this->request->getBaseUrl();
                $messages = $this->installer->createFinalConfigStep($siteUrl);

                if (!empty($messages)) {
                    $this->handleInstallerErrors($form, $messages);
                }

                return $this->postActionRedirect(
                    [
                        'viewParameters'    => [
                            'welcome_url' => $this->generateUrl('mautic_dashboard_index'),
                            'parameters'  => $this->configurator->render(),
                            'version'     => MAUTIC_VERSION,
                            'tmpl'        => $tmpl,
                        ],
                        'returnUrl'         => $this->generateUrl('mautic_installer_final'),
                        'contentTemplate'   => 'MauticInstallBundle:Install:final.html.php',
                        'forwardController' => false,
                    ]
                );
            }
        } else {
            // Redirect back to last step if the user advanced ahead via the URL
            $last = (int) end($completedSteps) + 1;
            if ($index && $index > $last) {
                return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => $last]));
            }
        }

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'form'           => $form->createView(),
                    'index'          => $index,
                    'count'          => $this->configurator->getStepCount(),
                    'version'        => MAUTIC_VERSION,
                    'tmpl'           => $tmpl,
                    'majors'         => $this->configurator->getRequirements(),
                    'minors'         => $this->configurator->getOptionalSettings(),
                    'appRoot'        => $this->getParameter('kernel.root_dir'),
                    'cacheDir'       => $this->getParameter('kernel.cache_dir'),
                    'logDir'         => $this->getParameter('kernel.logs_dir'),
                    'configFile'     => $this->get('mautic.helper.paths')->getSystemPath('local_config'),
                    'completedSteps' => $completedSteps,
                ],
                'contentTemplate' => $step->getTemplate(),
                'passthroughVars' => [
                    'route' => $this->generateUrl('mautic_installer_step', ['index' => $index]),
                ],
            ]
        );
    }

    /**
     * Controller action for the final step.
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function finalAction()
    {
        $session = $this->get('session');

        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app is installed and redirect
        if ($this->installer->checkIfInstalled()) {
            if (!$session->has('mautic.installer.completedsteps')) {
                // Arrived here by directly browsing to URL so redirect to the dashboard

                return $this->redirect($this->generateUrl('mautic_dashboard_index'));
            }
        } else {
            // Shouldn't have made it to this step without having a successful install
            return $this->redirect($this->generateUrl('mautic_installer_home'));
        }

        // Remove installer session variables
        $session->remove('mautic.installer.completedsteps');
        $session->remove('mautic.installer.user');

        $this->installer->finalMigrationStep();

        $welcomeUrl = $this->generateUrl('mautic_dashboard_index');

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'welcome_url' => $welcomeUrl,
                    'parameters'  => $this->configurator->render(),
                    'config_path' => $this->get('mautic.helper.paths')->getSystemPath('local_config'),
                    'is_writable' => $this->configurator->isFileWritable(),
                    'version'     => MAUTIC_VERSION,
                    'tmpl'        => $tmpl,
                ],
                'contentTemplate' => 'MauticInstallBundle:Install:final.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_installer_index',
                    'mauticContent' => 'installer',
                    'route'         => $this->generateUrl('mautic_installer_final'),
                ],
            ]
        );
    }

    /**
     * Handle installer errors.
     */
    private function handleInstallerErrors(Form $form, array $messages)
    {
        foreach ($messages as $type => $message) {
            switch ($type) {
                case 'warning':
                case 'error':
                case 'notice':
                    $this->addFlash($message, [], $type);
                    break;
                default:
                    // If type not a flash type, assume form field error
                    $form[$type]->addError(new FormError($message));
                    break;
            }
        }
    }
}
