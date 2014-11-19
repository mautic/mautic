<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Controller;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * InstallController.
 */
class InstallController extends CommonController
{
    /**
     * Controller action for install steps
     *
     * @param integer $index The step number to process
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stepAction($index = 0)
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app is installed and redirect
        if ($this->checkIfInstalled()) {
            return $this->redirect($this->generateUrl('mautic_dashboard_index'));
        }

        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $action = $this->generateUrl('mautic_installer_step', array('index' => $index));
        $step   = $configurator->getStep($index);

        /** @var \Symfony\Component\Form\Form $form */
        $form   = $this->container->get('form.factory')->create($step->getFormType(), $step, array('action' => $action));
        $tmpl   = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        // Always pass the requirements into the templates
        $majors = $configurator->getRequirements();
        $minors = $configurator->getOptionalSettings();

        if ('POST' === $this->request->getMethod()) {
            $form->submit($this->request);
            if ($form->isValid()) {
                $configurator->mergeParameters($step->update($form->getData()));

                try {
                    $configurator->write();
                } catch (RuntimeException $exception) {
                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'    => $form->createView(),
                            'index'   => $index,
                            'count'   => $configurator->getStepCount(),
                            'version' => $this->factory->getVersion(),
                            'tmpl'    => $tmpl,
                            'majors'  => $majors,
                            'minors'  => $minors,
                            'appRoot' => $this->container->getParameter('kernel.root_dir'),
                        ),
                        'returnUrl'         => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                        'contentTemplate'   => $step->getTemplate(),
                        'passthroughVars'   => array(
                            'activeLink'    => '#mautic_installer_index',
                            'mauticContent' => 'installer'
                        ),
                        'flashes'           => array(
                            array(
                                'type'    => 'error',
                                'msg'     => 'mautic.installer.error.writing.configuration'
                            )
                        ),
                        'forwardController' => false
                    ));
                }

                // Post-step processing
                $flashes = array();

                switch ($index) {
                    case 1:
                        $result = $this->performDatabaseInstallation($form, $configurator, $step);
                        if (is_array($result)) {
                            $flashes[] = $result;
                        }

                        $result = $this->performFixtureInstall();
                        if (is_array($result)) {
                            $flashes[] = $result;
                        }

                        break;

                    case 2:
                        $result = $this->performUserAddition($form);
                        if (is_array($result)) {
                            $flashes[] = $result;
                        }

                        break;

                    default:
                        $result = true;
                }

                // On a failure, the result will be an array; for success it will be a boolean
                if (!empty($flashes)) {
                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'    => $form->createView(),
                            'index'   => $index,
                            'count'   => $configurator->getStepCount(),
                            'version' => $this->factory->getVersion(),
                            'tmpl'    => $tmpl,
                            'majors'  => $majors,
                            'minors'  => $minors,
                            'appRoot' => $this->container->getParameter('kernel.root_dir'),
                        ),
                        'returnUrl'         => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                        'contentTemplate'   => $step->getTemplate(),
                        'passthroughVars'   => array(
                            'activeLink'    => '#mautic_installer_index',
                            'mauticContent' => 'installer'
                        ),
                        'flashes'           => $result,
                        'forwardController' => false
                    ));
                }

                $index++;

                if ($index < $configurator->getStepCount()) {
                    $nextStep = $configurator->getStep($index);
                    $action   = $this->generateUrl('mautic_installer_step', array('index' => $index));

                    $form = $this->container->get('form.factory')->create($nextStep->getFormType(), $nextStep, array('action' => $action));

                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'    => $form->createView(),
                            'index'   => $index,
                            'count'   => $configurator->getStepCount(),
                            'version' => $this->factory->getVersion(),
                            'tmpl'    => $tmpl,
                            'majors'  => $majors,
                            'minors'  => $minors,
                            'appRoot' => $this->container->getParameter('kernel.root_dir'),
                        ),
                        'returnUrl'         => $action,
                        'contentTemplate'   => $nextStep->getTemplate(),
                        'passthroughVars'   => array(
                            'activeLink'    => '#mautic_installer_index',
                            'mauticContent' => 'installer'
                        ),
                        'forwardController' => false
                    ));
                }

                /*
                 * Post-processing once installation is complete
                 */

                // Need to generate a secret value and merge it into the config
                $secret = hash('sha1', uniqid(mt_rand()));
                $configurator->mergeParameters(array('secret' => $secret));

                // Write the updated config file
                try {
                    $configurator->write();
                } catch (RuntimeException $exception) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.installer.error.writing.configuration'
                    );
                }

                // Clear the cache one final time with the updated config
                $this->clearCache();

                return $this->postActionRedirect(array(
                    'viewParameters'  =>  array(
                        'welcome_url' => $this->generateUrl('mautic_dashboard_index'),
                        'parameters'  => $configurator->render(),
                        'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                        'is_writable' => $configurator->isFileWritable(),
                        'version'     => $this->factory->getVersion(),
                        'tmpl'        => $tmpl,
                    ),
                    'returnUrl'         => $this->generateUrl('mautic_installer_final'),
                    'contentTemplate'   => 'MauticInstallBundle:Install:final.html.php',
                    'passthroughVars'   => array(
                        'activeLink'    => '#mautic_installer_index',
                        'mauticContent' => 'installer'
                    ),
                    'flashes'           => $flashes,
                    'forwardController' => false
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'    => $form->createView(),
                'index'   => $index,
                'count'   => $configurator->getStepCount(),
                'version' => $this->factory->getVersion(),
                'tmpl'    => $tmpl,
                'majors'  => $majors,
                'minors'  => $minors,
                'appRoot' => $this->container->getParameter('kernel.root_dir'),
            ),
            'contentTemplate' => $step->getTemplate(),
            'passthroughVars' => array(
                'activeLink'     => '#mautic_installer_index',
                'mauticContent'  => 'installer',
                'route'          => $this->generateUrl('mautic_installer_step', array('index' => $index))
            )
        ));
    }

    /**
     * Controller action for the final step
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function finalAction()
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app is installed and redirect
        if ($this->checkIfInstalled()) {
            return $this->redirect($this->generateUrl('mautic_dashboard_index'));
        }

        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $welcomeUrl = $this->generateUrl('mautic_dashboard_index');

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'welcome_url' => $welcomeUrl,
                'parameters'  => $configurator->render(),
                'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                'is_writable' => $configurator->isFileWritable(),
                'version'     => $this->factory->getVersion(),
                'tmpl'        => $tmpl,
            ),
            'contentTemplate' => 'MauticInstallBundle:Install:final.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_installer_index',
                'mauticContent'  => 'installer',
                'route'          => $this->generateUrl('mautic_installer_final')
            )
        ));
    }

    /**
     * Fetches the message to check if the database does not exist
     *
     * @param string $driver   Database driver
     * @param string $database Database name
     *
     * @return string
     */
    private function checkDatabaseNotExistsMessage($driver, $database)
    {
        switch ($driver) {
            case 'pdo_mysql':
                return "Unknown database '$database'";

            case 'pdo_pgsql':
                return 'database "' . $database . '" does not exist';
        }

        return '';
    }

    /**
     * Checks if the application has been installed and redirects if so
     *
     * @return bool
     */
    private function checkIfInstalled()
    {
        // If the config file doesn't even exist, no point in checking further
        if (file_exists($this->container->getParameter('kernel.root_dir') . '/config/local.php')) {
            /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
            $configurator = $this->container->get('mautic.configurator');
            $params       = $configurator->getParameters();

            // Check the DB Driver, Name, User, and send stats param
            if ((isset($params['db_driver']) && $params['db_driver'])
                && (isset($params['db_user']) && $params['db_user'])
                && (isset($params['db_name']) && $params['db_name'])
                && (isset($params['send_server_stats']) && $params['send_server_stats'])) {
                // We need to allow users to the final step, so one last check here
                if (strpos($this->request->getRequestUri(), 'installer/final') === false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Performs the database installation
     *
     * @param \Symfony\Component\Form\Form                          $form
     * @param \Mautic\InstallBundle\Configurator\Configurator       $configurator
     * @param \Mautic\InstallBundle\Configurator\Step\StepInterface $step
     *
     * @return array|boolean Array containing the flash message data on a failure, boolean true on success
     */
    private function performDatabaseInstallation($form, $configurator, $step)
    {
        $this->clearCache();

        $entityManager = $this->factory->getEntityManager();
        $metadatas     = $entityManager->getMetadataFactory()->getAllMetadata();
        $originalData  = $form->getData();

        if (!empty($metadatas)) {
            try {
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($metadatas);
            } catch (\Exception $exception) {
                $error = false;
                if (strpos($exception->getMessage(), $this->checkDatabaseNotExistsMessage($originalData->driver, $originalData->name)) !== false) {
                    // Try to manually create the database, first we null out the database name
                    $editData       = clone $originalData;
                    $editData->name = null;
                    $configurator->mergeParameters($step->update($editData));
                    $configurator->write();
                    $this->clearCache();
                    try {
                        $this->factory->getEntityManager()->getConnection()->executeQuery('CREATE DATABASE ' . $data->name);

                        // Assuming we got here, we should be able to install correctly now
                        $configurator->mergeParameters($step->update($originalData));
                        $configurator->write();
                        $this->clearCache();
                        $schemaTool = new SchemaTool($entityManager);
                        $schemaTool->createSchema($metadatas);
                    } catch (\Exception $exception) {
                        // We did our best, we really did
                        $error = true;
                        $msg   = 'mautic.installer.error.creating.database';
                    }
                } else {
                    $error = true;
                    if (strpos($exception->getMessage(), 'Base table or view already exists') !== false) {
                        $msg = 'mautic.installer.error.database.exists';
                    } else {
                        $msg = 'mautic.installer.error.creating.database';
                    }
                }

                if ($error) {
                    return array(
                        'type'    => 'error',
                        'msg'     => $msg,
                        'msgVars' => array('%exception%' => $exception->getMessage())
                    );
                }
            }
        } else {
            return array(
                'type' => 'error',
                'msg'  => 'mautic.installer.error.no.metadata'
            );
        }

        return true;
    }

    /**
     * Creates the admin user
     *
     * @param \Symfony\Component\Form\Form $form
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     */
    private function performUserAddition($form)
    {
        try {
            $entityManager = $this->factory->getEntityManager();

            // Now we create the user
            $data = $form->getData();
            $user = new User();

            /** @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
            $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);

            /** @var \Mautic\UserBundle\Model\RoleModel $model */
            $model = $this->factory->getModel('user.role');

            $user->setFirstName($data->firstname);
            $user->setLastName($data->lastname);
            $user->setUsername($data->username);
            $user->setEmail($data->email);
            $user->setPassword($encoder->encodePassword($data->password, $user->getSalt()));
            $user->setRole($model->getEntity(1));
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $exception) {
            return array(
                array(
                    'type'    => 'error',
                    'msg'     => 'mautic.installer.error.creating.user',
                    'msgVars' => array('%exception%' => $exception->getMessage())
                )
            );
        }

        return true;
    }

    /**
     * Installs data fixtures for the application
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     */
    private function performFixtureInstall()
    {
        try {
            // First we need to setup the environment
            $entityManager = $this->factory->getEntityManager();
            $paths         = array(dirname(__DIR__) . '/InstallFixtures/ORM');
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
        } catch (\Exception $exception) {
            return array(
                array(
                    'type'    => 'error',
                    'msg'     => 'mautic.installer.error.adding.fixtures',
                    'msgVars' => array('%exception%' => $exception->getMessage())
                )
            );
        }

        return true;
    }
}
