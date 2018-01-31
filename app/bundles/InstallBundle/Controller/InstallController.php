<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Controller;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\InstallBundle\Helper\SchemaHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * InstallController.
 */
class InstallController extends CommonController
{
    const CHECK_STEP    = 0;
    const DOCTRINE_STEP = 1;
    const USER_STEP     = 2;
    const EMAIL_STEP    = 3;

    /**
     * @var Configurator
     */
    private $configurator;

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->configurator = $this->container->get('mautic.configurator');
    }

    /**
     * Controller action for install steps.
     *
     * @param int $index The step number to process
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stepAction($index = 0)
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app
        // is installed and redirect
        if ($this->checkIfInstalled()) {
            return $this->redirect($this->generateUrl('mautic_dashboard_index'));
        }

        if (strpos($index, '.') !== false) {
            list($index, $subIndex) = explode('.', $index);
        }

        $params = $this->configurator->getParameters();
        $step   = $this->configurator->getStep($index)[0];
        $action = $this->generateUrl('mautic_installer_step', ['index' => $index]);

        $form = $this->createForm($step->getFormType(), $step, ['action' => $action]);
        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $session        = $this->get('session');
        $completedSteps = $session->get('mautic.installer.completedsteps', []);

        // Check to ensure the installer is in the right place
        if ((empty($params) || empty($params['db_driver'])) && $index > 1) {
            $session->set('mautic.installer.completedsteps', [0]);

            return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1]));
        }

        // Note if this step is complete
        $complete = false;

        if ('POST' === $this->request->getMethod()) {
            $form->handleRequest($this->request);
            if ($form->isValid()) {
                // Post-step processing
                $formData = $form->getData();

                switch ($index) {
                    case self::CHECK_STEP:
                        $complete = true;

                        break;
                    case self::DOCTRINE_STEP:
                        // password field does not retain configured defaults
                        if (empty($formData->password) && !empty($params['db_password'])) {
                            $formData->password = $params['db_password'];
                        }
                        $dbParams = (array) $formData;
                        $this->validateDatabaseParams($form, $dbParams);
                        if (!$form->getErrors(true)->count()) {
                            // Check if connection works and/or create database if applicable
                            $schemaHelper = new SchemaHelper($dbParams);

                            try {
                                $schemaHelper->testConnection();

                                if ($schemaHelper->createDatabase()) {
                                    $formData->server_version = $schemaHelper->getServerVersion();
                                    if ($this->saveConfiguration($formData, $step, true)) {
                                        // Refresh to install schema with new connection information in the container
                                        return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1.1]));
                                    }

                                    $this->addFlash('mautic.installer.error.writing.configuration', [], 'error');
                                } else {
                                    $this->addFlash('mautic.installer.error.creating.database', ['%name%' => $dbParams['name']], 'error');
                                }
                            } catch (\Exception $exception) {
                                $this->addFlash('mautic.installer.error.connecting.database', ['%exception%' => $exception->getMessage()], 'error');
                            }
                        }
                        break;

                    case self::USER_STEP:
                        try {
                            $this->createAdminUserStep($formData);

                            // Store the data to repopulate the form
                            unset($formData->password);
                            $session->set('mautic.installer.user', $formData);

                            $complete = true;
                        } catch (\Exception $exception) {
                            $this->addFlash('mautic.installer.error.creating.user', ['%exception%' => $exception->getMessage()], 'error');
                        }

                        break;

                    case self::EMAIL_STEP:
                        if (!$this->saveConfiguration($formData, $step)) {
                            $this->addFlash('mautic.installer.error.writing.configuration', [], 'error');
                        } else {
                            $complete = true;
                        }

                        break;
                }
            }
        } elseif (!empty($subIndex)) {
            switch ($index) {
                case self::DOCTRINE_STEP:
                    $dbParams     = (array) $step;
                    $schemaHelper = new SchemaHelper($dbParams);

                    $schemaHelper->setEntityManager($this->get('doctrine.orm.entity_manager'));

                    switch ((int) $subIndex) {
                        case 1:
                            try {
                                if (!$schemaHelper->installSchema()) {
                                    $this->addFlash('mautic.installer.error.no.metadata', [], 'error');
                                } else {
                                    return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1.2]));
                                }
                            } catch (\Exception $exception) {
                                $this->addFlash('mautic.installer.error.installing.data', ['%exception%' => $exception->getMessage(), 'error']);
                            }

                            return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1]));
                        case 2:
                            try {
                                $this->installDatabaseFixtures();
                                $complete = true;
                            } catch (\Exception $exception) {
                                $this->addFlash('mautic.installer.error.adding.fixtures', ['%exception%' => $exception->getMessage()], 'error');

                                return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => 1]));
                            }
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
                // Merge final things into the config, wipe the container, and we're done!
                $finalConfigVars = [
                    'secret_key' => EncryptionHelper::generateKey(),
                    'site_url'   => $this->request->getSchemeAndHttpHost().$this->request->getBaseUrl(),
                ];

                if (!$this->saveConfiguration($finalConfigVars, null, true)) {
                    $this->addFlash('mautic.installer.error.writing.configuration', [], 'error');
                }

                return $this->postActionRedirect(
                    [
                        'viewParameters' => [
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
                return $this->redirect($this->generateUrl('mautic_installer_step', ['index' => (int) $last]));
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'           => $form->createView(),
                    'index'          => $index,
                    'count'          => $this->configurator->getStepCount(),
                    'version'        => MAUTIC_VERSION,
                    'tmpl'           => $tmpl,
                    'majors'         => $this->configurator->getRequirements(),
                    'minors'         => $this->configurator->getOptionalSettings(),
                    'appRoot'        => $this->container->getParameter('kernel.root_dir'),
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function finalAction()
    {
        $session = $this->get('session');

        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app is installed and redirect
        if ($this->checkIfInstalled()) {
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

        // Add database migrations up to this point since this is a fresh install (must be done at this point
        // after the cache has been rebuilt
        $input  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
        $output = new BufferedOutput();

        $application = new Application($this->container->get('kernel'));
        $application->setAutoExit(false);
        $application->run($input, $output);

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
     * Checks if the application has been installed and redirects if so.
     *
     * @return bool
     */
    private function checkIfInstalled()
    {
        // If the config file doesn't even exist, no point in checking further
        $localConfigFile = $this->get('mautic.helper.paths')->getSystemPath('local_config');
        if (!file_exists($localConfigFile)) {
            return false;
        }

        /** @var \Mautic\CoreBundle\Configurator\Configurator $configurator */
        $params = $this->configurator->getParameters();

        // if db_driver and mailer_from_name are present then it is assumed all the steps of the installation have been
        // performed; manually deleting these values or deleting the config file will be required to re-enter
        // installation.
        if (empty($params['db_driver']) || empty($params['mailer_from_name'])) {
            return false;
        }

        return true;
    }

    /**
     * Installs data fixtures for the application.
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     */
    private function installDatabaseFixtures()
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $paths         = [dirname(__DIR__).'/InstallFixtures/ORM'];
        $loader        = new ContainerAwareLoader($this->container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $purger = new ORMPurger($entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($fixtures, true);
    }

    /**
     * Create the administrator user.
     *
     * @param array $data
     *
     * @return array|bool
     */
    private function createAdminUserStep($data)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        //ensure the username and email are unique
        try {
            $existingUser = $entityManager->getRepository('MauticUserBundle:User')->find(1);
        } catch (\Exception $e) {
            $existingUser = null;
        }

        if ($existingUser != null) {
            $user = $existingUser;
        } else {
            $user = new User();
        }

        /** @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        $user->setFirstName($data->firstname);
        $user->setLastName($data->lastname);
        $user->setUsername($data->username);
        $user->setEmail($data->email);
        $user->setPassword($encoder->encodePassword($data->password, $user->getSalt()));
        $user->setRole($entityManager->getReference('MauticUserBundle:Role', 1));

        $entityManager->persist($user);
        $entityManager->flush();
    }

    /**
     * @param $form
     * @param $dbParams
     */
    protected function validateDatabaseParams($form, $dbParams)
    {
        $translator = $this->get('translator');

        $required = [
            'host',
            'name',
            'user',
        ];

        foreach ($required as $r) {
            if (empty($dbParams[$r])) {
                $form[$r]->addError(new FormError($translator->trans('mautic.core.value.required', [], 'validators')));
            }
        }

        if ((int) $dbParams['port'] <= 0) {
            $form['port']->addError(new FormError($translator->trans('mautic.install.database.port.invalid', [], 'validators')));
        }
    }

    /**
     * @param array|StepInterface $params
     * @param null                $step
     * @param bool                $clearCache
     *
     * @return bool
     */
    protected function saveConfiguration($params, $step = null, $clearCache = false)
    {
        if (null !== $step) {
            $params = $step->update($params);
        }

        $this->configurator->mergeParameters($params);

        try {
            $this->configurator->write();
        } catch (\RuntimeException $exception) {
            return false;
        }

        if ($clearCache) {
            $this->get('mautic.helper.cache')->clearContainerFile(false);
        }

        return true;
    }
}
