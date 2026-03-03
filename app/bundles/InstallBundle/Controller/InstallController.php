<?php

namespace Mautic\InstallBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends CommonController
{
    public function __construct(
        private Configurator $configurator,
        private InstallService $installer,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * Controller action for install steps.
     *
     * @param int $index The step number to process
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function stepAction(Request $request, EntityManagerInterface $entityManager, PathsHelper $pathsHelper, float $index = 0): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app
        // is installed and redirect
        if ($this->installer->checkIfInstalled()) {
            return $this->redirectToRoute('mautic_dashboard_index');
        }

        if ($index - floor($index) > 0) {
            $subIndex = (int) (round($index - floor($index), 1) * 10);
            $index    = floor($index);
        }
        $index = (int) $index;

        $params = $this->configurator->getParameters();

        $session        = $request->getSession();
        $completedSteps = $session->get('mautic.installer.completedsteps', []);

        // Check to ensure the installer is in the right place
        if ((empty($params) || empty($params['db_driver'])) && $index > 1) {
            $session->set('mautic.installer.completedsteps', [0]);

            return $this->redirectToRoute('mautic_installer_step', ['index' => 1]);
        }

        $step   = $this->configurator->getStep($index)[0];
        \assert($step instanceof StepInterface);
        $action = $this->generateUrl('mautic_installer_step', ['index' => $index]);

        $form = $this->createForm($step->getFormType(), $step, ['action' => $action]);
        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        // Note if this step is complete
        $complete = false;

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // Post-step processing
                $formData = (array) $form->getData();

                switch ($index) {
                    case InstallService::CHECK_STEP:
                        $complete = true;

                        break;
                    case InstallService::DOCTRINE_STEP:
                        // password field does not retain configured defaults
                        if (empty($formData['password']) && !empty($params['db_password'])) {
                            $formData['password'] = $params['db_password'];
                        }
                        $messages = $this->installer->createDatabaseStep($step, $formData);
                        if (!empty($messages)) {
                            $this->handleInstallerErrors($form, $messages);
                            break;
                        }

                        /**
                         * We need to clear the ORM metadata cache before creating the schema. If the user provided a database
                         * table prefix in the UI installer, cached table names don't have the prefix yet (e.g. oauth2_clients).
                         * After clearing the metadata cache, Doctrine automatically recreates it with the correct prefixes (e.g.
                         * mau_oauth2_clients), if applicable.
                         */
                        $entityManager->getConfiguration()->getMetadataCache()->clear();

                        // Refresh to install schema with new connection information in the container
                        return $this->redirectToRoute('mautic_installer_step', ['index' => 1.1]);
                    case InstallService::USER_STEP:
                        $messages = $this->installer->createAdminUserStep($formData);

                        if (!empty($messages)) {
                            $this->handleInstallerErrors($form, $messages);
                            break;
                        }

                        // Store the data to repopulate the form
                        unset($formData['password']);
                        $session->set('mautic.installer.user', $formData);

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

                                return $this->redirectToRoute('mautic_installer_step', ['index' => 1]);
                            }

                            return $this->redirectToRoute('mautic_installer_step', ['index' => 1.2]);
                        case 2:
                            $messages = $this->installer->createFixturesStep();
                            if (!empty($messages)) {
                                $this->handleInstallerErrors($form, $messages);

                                return $this->redirectToRoute('mautic_installer_step', ['index' => 1]);
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

                return $this->redirectToRoute('mautic_installer_step', ['index' => (int) $index]);
            } else {
                $siteUrl  = $request->getSchemeAndHttpHost().$request->getBaseUrl();
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
                        'contentTemplate'   => '@MauticInstall/Install/final.html.twig',
                        'forwardController' => false,
                    ]
                );
            }
        } else {
            // Redirect back to last step if the user advanced ahead via the URL
            $last = (int) end($completedSteps) + 1;
            if ($index && $index > $last) {
                return $this->redirectToRoute('mautic_installer_step', ['index' => $last]);
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
                    'appRoot'        => $this->coreParametersHelper->get('mautic.application_dir').'/app',
                    'cacheDir'       => $this->coreParametersHelper->get('kernel.cache_dir'),
                    'logDir'         => $this->coreParametersHelper->get('kernel.logs_dir'),
                    'configFile'     => ParameterLoader::getLocalConfigFile($pathsHelper->getSystemPath('root').'/app'),
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
     * @throws \Exception
     */
    public function finalAction(Request $request, PathsHelper $pathsHelper): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $session = $request->getSession();

        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app is installed and redirect
        if ($this->installer->checkIfInstalled()) {
            if (!$session->has('mautic.installer.completedsteps')) {
                // Arrived here by directly browsing to URL so redirect to the dashboard

                return $this->redirectToRoute('mautic_dashboard_index');
            }
        } else {
            // Shouldn't have made it to this step without having a successful install
            return $this->redirectToRoute('mautic_installer_home');
        }

        // Remove installer session variables
        $session->remove('mautic.installer.completedsteps');
        $session->remove('mautic.installer.user');

        $this->installer->finalMigrationStep();

        $welcomeUrl = $this->generateUrl('mautic_dashboard_index');

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'welcome_url' => $welcomeUrl,
                    'parameters'  => $this->configurator->render(),
                    'config_path' => ParameterLoader::getLocalConfigFile($pathsHelper->getSystemPath('root').'/app'),
                    'is_writable' => $this->configurator->isFileWritable(),
                    'version'     => MAUTIC_VERSION,
                    'tmpl'        => $tmpl,
                ],
                'contentTemplate' => '@MauticInstall/Install/final.html.twig',
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
    private function handleInstallerErrors(FormInterface $form, array $messages): void
    {
        foreach ($messages as $type => $message) {
            match ($type) {
                'warning', 'error', 'notice' => $this->addFlashMessage($message, [], $type),
                default => $form[$type]->addError(new FormError($message)),
            };
        }
    }
}
