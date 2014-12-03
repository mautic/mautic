<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Controller;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Form\FormError;
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
    public function stepAction ($index = 0)
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
        $form = $this->container->get('form.factory')->create($step->getFormType(), $step, array('action' => $action));
        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        // Always pass the requirements into the templates
        $majors = $configurator->getRequirements();
        $minors = $configurator->getOptionalSettings();

        $session        = $this->factory->getSession();
        $completedSteps = $session->get('mautic.install.completedsteps', array());

        if ('POST' === $this->request->getMethod()) {
            $form->handleRequest($this->request);
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
                                'type' => 'error',
                                'msg'  => 'mautic.installer.error.writing.configuration'
                            )
                        ),
                        'forwardController' => false
                    ));
                }

                // Post-step processing
                $flashes      = array();
                $originalData = $form->getData();
                $success      = true;

                switch ($index) {
                    case 1:
                        //let's create a dynamic details
                        $dbParams           = (array)$originalData;
                        $dbParams['dbname'] = $dbParams['name'];
                        unset($dbParams['name']);

                        $result = $this->performDatabaseInstallation($dbParams);
                        if (is_array($result)) {
                            $flashes[] = $result;
                        } else {
                            //write the working database details to the configuration file
                            $configurator->mergeParameters($step->update($originalData));
                            $configurator->write();

                            $result = $this->performFixtureInstall($dbParams);
                            if (is_array($result)) {
                                $flashes[] = $result;
                            }
                        }

                        break;

                    case 2:
                        $entityManager = $this->getEntityManager();

                        //ensure the username and email are unique
                        $existing = $entityManager->getRepository('MauticUserBundle:User')->checkUniqueUsernameEmail(array(
                            'username' => $originalData->username,
                            'email'    => $originalData->email
                        ));

                        if (!empty($existing)) {
                            $translator = $this->factory->getTranslator();
                            if ($existing[0]->getEmail() == $originalData->email) {
                                $form['email']->addError(new FormError(
                                    $translator->trans('mautic.user.user.email.unique', array(), 'validators')
                                ));
                            }

                            if ($existing[0]->getUsername() == $originalData->username) {
                                $form['username']->addError(new FormError(
                                    $translator->trans('mautic.user.user.username.unique', array(), 'validators')
                                ));
                            }

                            $success = false;
                        } else {
                            $result = $this->performUserAddition($form);
                            if (is_array($result)) {
                                $flashes[] = $result;
                            }
                        }

                        break;
                }

                if ($success) {
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
                            'flashes'           => $flashes,
                            'forwardController' => false
                        ));
                    }

                    $completedSteps[] = $index;
                    $session->set('mautic.install.completedsteps', $completedSteps);
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
                            'type' => 'error',
                            'msg'  => 'mautic.installer.error.writing.configuration'
                        );
                    }

                    // Clear the cache one final time with the updated config
                    $this->clearCache();

                    $session->remove('mautic.install.completedsteps');

                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'welcome_url' => $this->generateUrl('mautic_dashboard_index'),
                            'parameters'  => $configurator->render(),
                            'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                            'is_writable' => $configurator->isFileWritable(),
                            'version'     => $this->factory->getVersion(),
                            'tmpl'        => $tmpl,
                        ),
                        'returnUrl'         => $this->generateUrl('mautic_installer_final'),
                        'contentTemplate'   => 'MauticInstallBundle:Install:final.html.php',
                        'flashes'           => $flashes,
                        'forwardController' => false
                    ));
                }
            }
        } else {
            //redirect back to last step if the user advanced ahead via the URL
            $last = (int) end($completedSteps) + 1;
            if ($index > $last) {
                return $this->redirect($this->generateUrl('mautic_installer_step', array('index' => (int) $last)));
            }

        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'    => $form->createView(),
                'index'   => $index,
                'count'   => $configurator->getStepCount(),
                'version' => $this->factory->getVersion(),
                'tmpl'    => $tmpl,
                'majors'  => $majors,
                'minors'  => $minors,
                'appRoot' => $this->container->getParameter('kernel.root_dir'),
                'completedSteps' => $completedSteps
            ),
            'contentTemplate' => $step->getTemplate(),
            'passthroughVars' => array(
                'route' => $this->generateUrl('mautic_installer_step', array('index' => $index))
            )
        ));
    }

    /**
     * Controller action for the final step
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function finalAction ()
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
            'viewParameters'  => array(
                'welcome_url' => $welcomeUrl,
                'parameters'  => $configurator->render(),
                'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                'is_writable' => $configurator->isFileWritable(),
                'version'     => $this->factory->getVersion(),
                'tmpl'        => $tmpl,
            ),
            'contentTemplate' => 'MauticInstallBundle:Install:final.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_installer_index',
                'mauticContent' => 'installer',
                'route'         => $this->generateUrl('mautic_installer_final')
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
    private function checkDatabaseNotExistsMessage ($driver, $database)
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
    private function checkIfInstalled ()
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
                && (isset($params['send_server_stats']) && $params['send_server_stats'])
            ) {
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
     * @param array $dbParams
     *
     * @return array|boolean Array containing the flash message data on a failure, boolean true on success
     */
    private function performDatabaseInstallation ($dbParams)
    {
        $dbName             = $dbParams['dbname'];
        $dbParams['dbname'] = null;

        //test credentials
        try {
            $db = DriverManager::getConnection($dbParams);
            $db->connect();
        } catch (\Exception $exception) {
            $db->close();

            return array(
                'type' => 'error',
                'msg'  => 'mautic.installer.error.connecting.database',
                'msgVars' => array(
                    '%exception%' => $exception->getMessage()
                )
            );
        }

        //test database existence
        $dbParams['dbname'] = $dbName;

        //suppress display of errors as we know its going to happen while testing the connection
        ini_set('display_errors', 0);

        $db = DriverManager::getConnection($dbParams);
        if ($db->isConnected()) {
            $db->close();
        }

        try {
            //test credentials
            $db->connect();
            $schemaManager = $db->getSchemaManager();

        } catch (\Exception $exception) {
            $db->close();

            //it failed to connect so remove the dbname and try to create it
            $dbParams['dbname'] = null;
            $db                 = DriverManager::getConnection($dbParams);
            $db->connect();

            try {
                //database does not exist so try to create it
                $schemaManager = $db->getSchemaManager();
                $schemaManager->createDatabase($dbName);

                //close the connection and reconnect with the new database name
                $db->close();

                $dbParams['dbname'] = $dbName;
                $db                 = DriverManager::getConnection($dbParams);
                $schemaManager      = $db->getSchemaManager();
            } catch (\Exception $exception) {
                $db->close();

                return array(
                    'type'    => 'error',
                    'msg'     => 'mautic.installer.error.creating.database',
                    'msgVars' => array(
                        '%name%' => $dbName
                    )
                );
            }
        }

        try {
            //check to see if the table already exist
            $tables = $schemaManager->listTableNames();
        } catch (\Exception $e) {
            $db->close();

            return array(
                'type'    => 'error',
                'msg'     => 'mautic.installer.error.connecting.database',
                'msgVars' => array(
                    '%exception%' => $e->getMessage()
                )
            );
        }

        if ($dbParams['backup_tables']) {
            //backup existing tables
            $backupPrefix     = ($dbParams['backup_prefix']) ? $dbParams['backup_prefix'] : 'bak_';
            $backupRestraints = $dropTables = $backupTables = array();

            //cycle through the first time to drop all the foreign keys
            foreach ($tables as $t) {
                $backup = str_replace($dbParams['table_prefix'], $backupPrefix, $t);

                $restraints = $schemaManager->listTableForeignKeys($t);

                if ($t != $backup) {
                    //to be backed up
                    $backupRestraints[$backup] = $restraints;
                    $backupTables[$t]          = $backup;
                } else {
                    //existing backup to be dropped
                    $dropTables[] = $t;
                }

                foreach ($restraints as $restraint) {
                    $schemaManager->dropForeignKey($restraint, $t);
                }
            }

            //now drop all the backup tables
            foreach ($dropTables as $t) {
                $schemaManager->dropTable($t);
            }

            //now backup tables
            foreach ($backupTables as $t => $backup) {
                $schemaManager->renameTable($t, $backup);
            }

            //apply foreign keys to backup tables
            foreach ($backupRestraints as $table => $oldRestraints) {
                foreach ($oldRestraints as $or) {
                    $foreignTable     = $or->getForeignTableName();
                    $foreignTableName = str_replace($dbParams['table_prefix'], $backupPrefix, $foreignTable);
                    $r                = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(
                        $or->getLocalColumns(),
                        $foreignTableName,
                        $or->getForeignColumns(),
                        'BAK_' . $or->getName(),
                        $or->getOptions()
                    );
                    $schemaManager->createForeignKey($r, $table);
                }
            }
        } else {
            //drop tables
            foreach ($tables as $t) {
                //drop foreign keys first in order to be able to drop the table
                $restraints = $schemaManager->listTableForeignKeys($t);
                foreach ($restraints as $restraint) {
                    $schemaManager->dropForeignKey($restraint, $t);
                }
            }
            foreach ($tables as $t) {
                $schemaManager->dropTable($t);
            }
        }

        $entityManager = $this->getEntityManager($dbParams);
        $metadatas     = $entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadatas)) {
            $schemaTool = new SchemaTool($entityManager);
            $queries    = $schemaTool->getCreateSchemaSql($metadatas);

            foreach ($queries as $q) {
                try {
                    $db->executeQuery($q);
                } catch (\Exception $e) {
                    $db->close();

                    return array(
                        'type'    => 'error',
                        'msg'     => 'mautic.installer.error.installing.data',
                        'msgVars' => array(
                            '%exception%' => $e->getMessage()
                        )
                    );
                }
            }
        } else {
            $db->close();

            return array(
                'type' => 'error',
                'msg'  => 'mautic.installer.error.no.metadata'
            );
        }
        $db->close();

        return true;
    }

    /**
     * Installs data fixtures for the application
     *
     * @param array $dbParams
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     */
    private function performFixtureInstall ()
    {
        try {
            $entityManager = $this->getEntityManager();
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
                    sprintf('Could not find any fixtures to load in: %s', "\n\n- " . implode("\n- ", $paths))
                );
            }

            $purger = new ORMPurger($entityManager);
            $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
            $executor = new ORMExecutor($entityManager, $purger);
            $executor->execute($fixtures, true);
        } catch (\Exception $exception) {
            return array(
                'type'    => 'error',
                'msg'     => 'mautic.installer.error.adding.fixtures',
                'msgVars' => array('%exception%' => $exception->getMessage())
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
    private function performUserAddition ($form)
    {
        try {
            $entityManager = $this->getEntityManager();

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
                'type'    => 'error',
                'msg'     => 'mautic.installer.error.creating.user',
                'msgVars' => array('%exception%' => $exception->getMessage())
            );
        }

        return true;
    }

    /**
     * Build an entity manager specific for the installer to prevent cache related issues
     *
     * @param $dbParams
     *
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    private function getEntityManager ($dbParams = array())
    {
        static $entityManager;

        if (empty($entityManager)) {
            if (empty($dbParams)) {
                $configurator = $this->container->get('mautic.configurator');
                $dbStep       = new DoctrineStep($configurator->getParameters());
                $dbParams     = (array)$dbStep;

                $dbParams['dbname'] = $dbParams['name'];
                unset($dbParams['name']);
            }

            $paths = $namespaces = array();

            //build entity namespaces
            $bundles = $this->factory->getParameter('bundles');
            foreach ($bundles as $b) {
                $entityPath = $b['directory'] . '/Entity';
                if (file_exists($entityPath)) {
                    $paths[] = $entityPath;
                    $namespaces['Mautic' . $b['bundle']] = $b['namespace'] . '\Entity';
                }
            }

            $addons = $this->factory->getParameter('addon.bundles');
            foreach ($addons as $b) {
                $entityPath = $b['directory'] . '/Entity';
                if (file_exists($entityPath)) {
                    $paths[] = $entityPath;
                    $namespaces[$b['bundle']] = $b['namespace'] . '\Entity';
                }
            }
            $config  = Setup::createAnnotationMetadataConfiguration($paths, true, null, null, false);
            foreach ($namespaces as $alias => $namespace) {
                $config->addEntityNamespace($alias, $namespace);
            }

            //set the table prefix
            define('MAUTIC_TABLE_PREFIX', $dbParams['table_prefix']);

            //reset database classes for fixtures that load from container and/or MauticFactory

            //Add the event listener that adds the table prefix to entity metadata
            $eventManager = new EventManager();
            $eventManager->addEventSubscriber(new DoctrineEventsSubscriber());

            $db = DriverManager::getConnection($dbParams, null, $eventManager);
            $this->container->set('database_connection', $db);
            $this->factory->setDatabase($db);

            $entityManager = EntityManager::create($db, $config, $eventManager);

            $this->container->set('doctrine.orm.entity_manager', $entityManager);
            $this->factory->setEntityManager($entityManager);

            $this->container->set('mautic.factory', $this->factory);
        }

        return $entityManager;
    }
}
