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
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\FormError;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

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
        $params       = $configurator->getParameters();
        $step         = $configurator->getStep($index);
        $action       = $this->generateUrl('mautic_installer_step', array('index' => $index));

        /** @var \Symfony\Component\Form\Form $form */
        $form = $this->container->get('form.factory')->create($step->getFormType(), $step, array('action' => $action));
        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        // Always pass the requirements into the templates
        $majors = $configurator->getRequirements();
        $minors = $configurator->getOptionalSettings();

        $session        = $this->factory->getSession();
        $completedSteps = $session->get('mautic.installer.completedsteps', array());

        // Check to ensure the installer is in the right place
        if ((empty($params) || empty($params['db_driver'])) && $index > 1) {
            $session->set('mautic.installer.completedsteps', array(0));
            return $this->redirect($this->generateUrl('mautic_installer_step', array('index' => 1)));
        }

        if ('POST' === $this->request->getMethod()) {
            $form->handleRequest($this->request);
            if ($form->isValid()) {
                $configurator->mergeParameters($step->update($form->getData()));

                try {
                    $configurator->write();
                } catch (RuntimeException $exception) {
                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'       => $form->createView(),
                            'index'      => $index,
                            'count'      => $configurator->getStepCount(),
                            'version'    => $this->factory->getVersion(),
                            'tmpl'       => $tmpl,
                            'majors'     => $majors,
                            'minors'     => $minors,
                            'appRoot'    => $this->container->getParameter('kernel.root_dir'),
                            'configFile' => $this->factory->getLocalConfigFile()
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
                        $dbParams = (array)$originalData;

                        //validate settings
                        $dbValid    = false;
                        $translator = $this->factory->getTranslator();
                        if ($dbParams['driver'] == 'pdo_sqlite') {
                            if (empty($dbParams['path'])) {
                                $form['path']->addError(new FormError($translator->trans('mautic.core.value.required', array(), 'validators')));
                            } elseif (!is_writable(dirname($dbParams['path'])) || substr($dbParams['path'], -4) != '.db3') {
                                $form['path']->addError(new FormError($translator->trans('mautic.install.database.path.invalid', array(), 'validators')));
                            } else {
                                $root = $this->factory->getSystemPath('root');
                                if (strpos(dirname($dbParams['path']), $root) !== false) {
                                    $flashes[] = array(
                                        'msg'     => 'mautic.install.database.path.warning',
                                        'msgVars' => array('%root%' => $root),
                                        'domain'  => 'validators'
                                    );
                                }
                                $dbValid = true;
                            }

                            if (!file_exists($dbParams['path'])) {
                                //create the file
                                file_put_contents($dbParams['path'], '');
                            }
                        } else {
                            $required = array(
                                'host',
                                'name',
                                'user'
                            );
                            foreach ($required as $r) {
                                if (empty($dbParams[$r])) {
                                    $form[$r]->addError(new FormError($translator->trans('mautic.core.value.required', array(), 'validators')));
                                }
                            }

                            if ((int) $dbParams['port'] <= 0) {
                                $form['port']->addError(new FormError($translator->trans('mautic.install.database.port.invalid', array(), 'validators')));
                            } else {
                                $dbValid = true;
                            }
                        }

                        if (!$dbValid) {
                            $success = false;
                        } else {
                            $dbParams['dbname'] = $dbParams['name'];
                            unset($dbParams['name']);

                            $result = $this->performDatabaseInstallation($dbParams);

                            if (is_array($result)) {
                                $flashes[] = $result;
                                $success   = false;
                            } else {
                                //write the working database details to the configuration file
                                $configurator->mergeParameters($step->update($originalData));
                                $configurator->write();

                                $result = $this->performFixtureInstall($dbParams);
                                if (is_array($result)) {
                                    $flashes[] = $result;
                                    $success   = false;
                                }
                            }
                        }

                        break;

                    case 2:
                        $entityManager = $this->getEntityManager();

                        //ensure the username and email are unique
                        try {
                            $existing = $entityManager->getRepository('MauticUserBundle:User')->find(1);
                        } catch (\Exception $e) {
                            $existing = null;
                        }

                        if (!empty($existing)) {
                            $result = $this->performUserAddition($form, $existing);
                        } else {
                            $result = $this->performUserAddition($form);
                        }

                        if (is_array($result)) {
                            $flashes[] = $result;
                            $success   = false;
                        }

                        //store the data
                        unset($originalData->password);
                        $session->set('mautic.installer.user', $originalData);

                        break;

                    default:
                        $success = true;

                }

                if ($success) {
                    $completedSteps[] = $index;
                    $session->set('mautic.installer.completedsteps', $completedSteps);
                    $index++;

                    if ($index < $configurator->getStepCount()) {
                        $nextStep = $configurator->getStep($index);
                        $action   = $this->generateUrl('mautic_installer_step', array('index' => $index));

                        $form = $this->container->get('form.factory')->create($nextStep->getFormType(), $nextStep, array('action' => $action));

                        return $this->postActionRedirect(array(
                            'viewParameters'    => array(
                                'form'       => $form->createView(),
                                'index'      => $index,
                                'count'      => $configurator->getStepCount(),
                                'version'    => $this->factory->getVersion(),
                                'tmpl'       => $tmpl,
                                'majors'     => $majors,
                                'minors'     => $minors,
                                'appRoot'    => $this->container->getParameter('kernel.root_dir'),
                                'configFile' => $this->factory->getLocalConfigFile()
                            ),
                            'flashes'           => $flashes,
                            'returnUrl'         => $action,
                            'contentTemplate'   => $nextStep->getTemplate(),
                            'forwardController' => false
                        ));
                    }

                    /*
                     * Post-processing once installation is complete
                     */

                    // Need to generate a secret value and merge it into the config
                    $configurator->mergeParameters(array('secret_key' => EncryptionHelper::generateKey()));

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
                    $this->clearCacheFile();

                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'welcome_url' => $this->generateUrl('mautic_dashboard_index'),
                            'parameters'  => $configurator->render(),
                            'config_path' => $this->factory->getLocalConfigFile(),
                            'is_writable' => $configurator->isFileWritable(),
                            'version'     => $this->factory->getVersion(),
                            'tmpl'        => $tmpl,
                        ),
                        'returnUrl'         => $this->generateUrl('mautic_installer_final'),
                        'contentTemplate'   => 'MauticInstallBundle:Install:final.html.php',
                        'flashes'           => $flashes,
                        'forwardController' => false
                    ));
                } elseif (!empty($flashes)) {
                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'       => $form->createView(),
                            'index'      => $index,
                            'count'      => $configurator->getStepCount(),
                            'version'    => $this->factory->getVersion(),
                            'tmpl'       => $tmpl,
                            'majors'     => $majors,
                            'minors'     => $minors,
                            'appRoot'    => $this->container->getParameter('kernel.root_dir'),
                            'configFile' => $this->factory->getLocalConfigFile()
                        ),
                        'returnUrl'         => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                        'contentTemplate'   => $step->getTemplate(),
                        'flashes'           => $flashes,
                        'forwardController' => false
                    ));
                }
            }
        } else {
            //redirect back to last step if the user advanced ahead via the URL
            $last = (int)end($completedSteps) + 1;
            if ($index > $last) {
                return $this->redirect($this->generateUrl('mautic_installer_step', array('index' => (int)$last)));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'           => $form->createView(),
                'index'          => $index,
                'count'          => $configurator->getStepCount(),
                'version'        => $this->factory->getVersion(),
                'tmpl'           => $tmpl,
                'majors'         => $majors,
                'minors'         => $minors,
                'appRoot'        => $this->container->getParameter('kernel.root_dir'),
                'configFile'     => $this->factory->getLocalConfigFile(),
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
        $session = $this->factory->getSession();

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
        $input  = new ArgvInput(array('console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction'));
        $output = new BufferedOutput();

        $application = new Application($this->factory->getKernel());
        $application->setAutoExit(false);
        $application->run($input, $output);

        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $welcomeUrl = $this->generateUrl('mautic_dashboard_index');

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'welcome_url' => $welcomeUrl,
                'parameters'  => $configurator->render(),
                'config_path' => $this->factory->getLocalConfigFile(),
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
     * Checks if the application has been installed and redirects if so
     *
     * @return bool
     */
    private function checkIfInstalled ()
    {
        // If the config file doesn't even exist, no point in checking further
        $localConfigFile = $this->factory->getLocalConfigFile();
        if (!file_exists($localConfigFile)) {
            return false;
        }

        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');
        $params       = $configurator->getParameters();

        // if db_driver and mailer_from_name are present then it is assumed all the steps of the installation have been
        // performed; manually deleting these values or deleting the config file will be required to re-enter
        // installation.
        if (empty($params['db_driver']) || empty($params['mailer_from_name'])) {
            return false;
        }

        return true;
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
        $dbName              = $dbParams['dbname'];
        $dbParams['dbname']  = null;
        $dbParams['charset'] = 'UTF8';

        //suppress display of errors as we know its going to happen while testing the connection
        ini_set('display_errors', 0);

        //test credentials
        try {
            $db = DriverManager::getConnection($dbParams);
            $db->connect();
        } catch (\Exception $exception) {
            $db->close();

            return array(
                'type'    => 'error',
                'msg'     => 'mautic.installer.error.connecting.database',
                'msgVars' => array(
                    '%exception%' => $exception->getMessage()
                )
            );
        }

        //test database existence
        $dbParams['dbname'] = $dbName;

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

        $sql          = array();
        $platform     = $schemaManager->getDatabasePlatform();
        $backupPrefix = (!empty($dbParams['backup_prefix'])) ? $dbParams['backup_prefix'] : 'bak_';

        // Generate install schema
        $entityManager = $this->getEntityManager($dbParams);
        $metadatas     = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool    = new SchemaTool($entityManager);
        $installSchema = $schemaTool->getSchemaFromMetadata($metadatas);
        $mauticTables  = $applicableSequences = array();

        foreach ($installSchema->getTables() as $m) {
            $tableName                = $m->getName();
            $mauticTables[$tableName] = $this->generateBackupName($dbParams['table_prefix'], $backupPrefix, $tableName);;
        }

        //backup sequences for databases that support
        try {
            $allSequences = $schemaManager->listSequences();
        } catch (\Exception $e) {
            $allSequences = array();
        }

        // Collect list of sequences
        /** @var \Doctrine\DBAL\Schema\Sequence $sequence */
        foreach ($allSequences as $sequence) {
            $name      = $sequence->getName();
            $tableName = str_replace('_id_seq', '', $name);
            if (isset($mauticTables[$tableName]) || in_array($tableName, $mauticTables)) {
                $applicableSequences[$tableName] = $sequence;
            }
        }
        unset($allSequences);

        if ($dbParams['backup_tables']) {
            //backup existing tables
            $backupRestraints = $backupSequences = $backupIndexes = $backupTables = $dropSequences = $dropTables = array();

            //cycle through the first time to drop all the foreign keys
            foreach ($tables as $t) {
                if (!isset($mauticTables[$t]) && !in_array($t, $mauticTables)) {
                    // Not an applicable table
                    continue;
                }

                $restraints = $schemaManager->listTableForeignKeys($t);

                if (isset($mauticTables[$t])) {
                    //to be backed up
                    $backupRestraints[$mauticTables[$t]] = $restraints;
                    $backupTables[$t]          = $mauticTables[$t];
                    $backupIndexes[$t]         = $schemaManager->listTableIndexes($t);

                    if (isset($applicableSequences[$t])) {
                        //backup the sequence
                        $backupSequences[$t] = $applicableSequences[$t];
                    }
                } else {
                    //existing backup to be dropped
                    $dropTables[] = $t;

                    if (isset($applicableSequences[$t])) {
                        //drop the sequence
                        $dropSequences[$t] = $applicableSequences[$t];
                    }
                }

                foreach ($restraints as $restraint) {
                    $sql[] = $platform->getDropForeignKeySQL($restraint, $t);
                }
            }

            //now drop all the backup tables
            foreach ($dropTables as $t) {
                $sql[] = $platform->getDropTableSQL($t);

                if (isset($dropSequences[$t])) {
                    $sql[] = $platform->getDropSequenceSQL($dropSequences[$t]);
                }
            }

            //now backup tables
            foreach ($backupTables as $t => $backup) {
                //drop old sequences
                if (isset($backupSequences[$t])) {
                    $oldSequence = $backupSequences[$t];
                    $name        = $oldSequence->getName();
                    $newName     = $this->generateBackupName($dbParams['table_prefix'], $backupPrefix, $name);
                    $newSequence = new Sequence(
                        $newName,
                        $oldSequence->getAllocationSize(),
                        $oldSequence->getInitialValue()
                    );

                    $sql[] = $platform->getDropSequenceSQL($oldSequence);
                }

                //drop old indexes
                /** @var \Doctrine\DBAL\Schema\Index $oldIndex */
                foreach ($backupIndexes[$t] as $indexName => $oldIndex) {
                    if ($indexName == 'primary') {
                        continue;
                    }

                    $oldName = $oldIndex->getName();
                    $newName = $this->generateBackupName($dbParams['table_prefix'], $backupPrefix, $oldName);

                    $newIndex = new Index(
                        $newName,
                        $oldIndex->getColumns(),
                        $oldIndex->isUnique(),
                        $oldIndex->isPrimary(),
                        $oldIndex->getFlags()
                    );

                    // Handle postgres primary key constraint
                    if (strpos($oldName, '_pkey') !== false) {
                        $newConstraint = $newIndex;
                        $sql[] = $platform->getDropConstraintSQL($oldName, $t);
                    } else {
                        $newIndexes[] = $newIndex;
                        $sql[]        = $platform->getDropIndexSQL($oldIndex, $t);
                    }
                }

                //rename table
                $tableDiff = new TableDiff($t);
                $tableDiff->newName = $backup;
                $queries = $platform->getAlterTableSQL($tableDiff);
                $sql     = array_merge($sql, $queries);

                //create new index
                if (!empty($newIndexes)) {
                    foreach ($newIndexes as $newIndex) {
                        $sql[] = $platform->getCreateIndexSQL($newIndex, $backup);
                    }
                    unset($newIndexes);
                }

                //create new sequence
                if (!empty($newSequence)) {
                    $sql[] = $platform->getCreateSequenceSQL($newSequence);
                    unset($newSequence);
                }

                //create new constraint
                if (!empty($newConstraint)) {
                    $sql[] = $platform->getCreateConstraintSQL($newConstraint, $backup);
                }
            }

            //apply foreign keys to backup tables
            foreach ($backupRestraints as $table => $oldRestraints) {
                foreach ($oldRestraints as $or) {
                    $foreignTable     = $or->getForeignTableName();
                    $foreignTableName = $this->generateBackupName($dbParams['table_prefix'], $backupPrefix, $foreignTable);
                    $r                = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(
                        $or->getLocalColumns(),
                        $foreignTableName,
                        $or->getForeignColumns(),
                        $backupPrefix . $or->getName(),
                        $or->getOptions()
                    );
                    $sql[] = $platform->getCreateForeignKeySQL($r, $table);
                }
            }
        } else {

            //drop and create new sequences
            /** @var \Doctrine\DBAL\Schema\Sequence $sequence */
            foreach ($applicableSequences as $sequence) {
                $sql[] = $platform->getDropSequenceSQL($sequence);
            }

            //drop tables
            foreach ($tables as $t) {
                if (isset($mauticTables[$t])) {
                    //drop foreign keys first in order to be able to drop the tables
                    $restraints = $schemaManager->listTableForeignKeys($t);
                    foreach ($restraints as $restraint) {
                        $sql[] = $platform->getDropForeignKeySQL($restraint, $t);
                    }
                }
            }
            foreach ($tables as $t) {
                if (isset($mauticTables[$t])) {
                    $sql[] = $platform->getDropTableSQL($t);
                }
            }
        }

        if (!empty($sql)) {
            foreach ($sql as $q) {
                try {
                    $db->query($q);
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
        }

        if (!empty($metadatas)) {
            $queries = $installSchema->toSql($platform);

            foreach ($queries as $q) {
                try {
                    $db->query($q);
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
     * @param $prefix
     * @param $backupPrefix
     * @param $name
     *
     * @return mixed|string
     */
    private function generateBackupName($prefix, $backupPrefix, $name)
    {
        if (empty($prefix) || strpos($name, $prefix) === false) {

            return $backupPrefix . $name;
        } else {

            return str_replace($prefix, $backupPrefix, $name);
        }
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
     * @param User                         $existingUser
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     */
    private function performUserAddition ($form, User $existingUser = null)
    {
        try {
            $entityManager = $this->getEntityManager();

            // Now we create the user
            $data = $form->getData();

            if ($existingUser != null) {
                $user = $existingUser;
            } else {
                $user = new User();
            }

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

            // Ensure UTF8 charset
            $dbParams['charset'] = 'UTF8';

            $paths = $namespaces = array();

            // Build entity namespaces
            $bundles = $this->factory->getMauticBundles(true);
            foreach ($bundles as $b) {
                $entityPath = $b['directory'] . '/Entity';
                if (file_exists($entityPath)) {
                    $paths[] = $entityPath;
                    if ($b['isAddon']) {
                        $namespaces[$b['bundle']] = $b['namespace'] . '\Entity';
                    } else {
                        $namespaces['Mautic' . $b['bundle']] = $b['namespace'] . '\Entity';
                    }
                }
            }

            $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, null, false);
            $config->setEntityNamespaces($namespaces);

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
