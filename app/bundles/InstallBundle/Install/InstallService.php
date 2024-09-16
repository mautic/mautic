<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Install;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\InstallBundle\Exception\AlreadyInstalledException;
use Mautic\InstallBundle\Exception\DatabaseVersionTooOldException;
use Mautic\InstallBundle\Helper\SchemaHelper;
use Mautic\UserBundle\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallService
{
    const CHECK_STEP    = 0;
    const DOCTRINE_STEP = 1;
    const USER_STEP     = 2;
    const EMAIL_STEP    = 3;
    const FINAL_STEP    = 4;

    private $configurator;

    private $cacheHelper;

    protected $pathsHelper;

    private $entityManager;

    private $translator;

    private $kernel;

    private $validator;

    private $encoder;

    /**
     * InstallService constructor.
     */
    public function __construct(Configurator $configurator,
                                CacheHelper $cacheHelper,
                                PathsHelper $pathsHelper,
                                EntityManager $entityManager,
                                TranslatorInterface $translator,
                                KernelInterface $kernel,
                                ValidatorInterface $validator,
                                UserPasswordEncoder $encoder)
    {
        $this->configurator             = $configurator;
        $this->cacheHelper              = $cacheHelper;
        $this->pathsHelper              = $pathsHelper;
        $this->entityManager            = $entityManager;
        $this->translator               = $translator;
        $this->kernel                   = $kernel;
        $this->validator                = $validator;
        $this->encoder                  = $encoder;
    }

    /**
     * Get step object for given index or appropriate step index.
     *
     * @param int $index The step number to retrieve
     *
     * @return StepInterface the valid step given installation status
     *
     * @throws \InvalidArgumentException|AlreadyInstalledException
     */
    public function getStep(int $index = 0): StepInterface
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app
        // is installed and redirect
        if ($this->checkIfInstalled()) {
            throw new AlreadyInstalledException();
        }

        $params = $this->configurator->getParameters();

        // Check to ensure the installer is in the right place
        if ((empty($params)
                || !isset($params['db_driver'])
                || empty($params['db_driver'])) && $index > 1) {
            return $this->configurator->getStep(self::DOCTRINE_STEP);
        }

        return $this->configurator->getStep($index)[0];
    }

    /**
     * Get local config file location.
     */
    private function localConfig(): string
    {
        return (string) $this->pathsHelper->getSystemPath('local_config', false);
    }

    /**
     * Get local config parameters.
     */
    public function localConfigParameters(): array
    {
        $localConfigFile = $this->localConfig();

        if (file_exists($localConfigFile)) {
            /** @var array $parameters */
            $parameters = [];

            // Load local config to override parameters
            include $localConfigFile;
            $localParameters = $parameters;
        } else {
            $localParameters = [];
        }

        return $localParameters;
    }

    /**
     * Checks if the application has been installed and redirects if so.
     */
    public function checkIfInstalled(): bool
    {
        // If the config file doesn't even exist, no point in checking further
        $localConfigFile = $this->localConfig();
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
     * Translation messages array.
     */
    private function translateMessages(array $messages): array
    {
        if (empty($messages)) {
            return $messages;
        }

        foreach ($messages as $key => $value) {
            $messages[$key] = $this->translator->trans($value);
        }

        return $messages;
    }

    /**
     * Checks for step's requirements.
     */
    public function checkRequirements(StepInterface $step): array
    {
        $messages = $step->checkRequirements();

        return $this->translateMessages($messages);
    }

    /**
     * Checks for step's optional settings.
     */
    public function checkOptionalSettings(StepInterface $step): array
    {
        $messages = $step->checkOptionalSettings();

        return $this->translateMessages($messages);
    }

    public function saveConfiguration($params, StepInterface $step = null, $clearCache = false): array
    {
        if ($step instanceof StepInterface) {
            $params = $step->update($step);
        }

        $this->configurator->mergeParameters($params);

        $messages = [];
        try {
            $this->configurator->write();
        } catch (\RuntimeException $exception) {
            $messages = [
                'error' => $this->translator->trans(
                    'mautic.installer.error.writing.configuration',
                    [],
                    'flashes'
                ),
            ];
        }

        if ($clearCache) {
            $this->cacheHelper->refreshConfig();
        }

        return $messages;
    }

    /**
     * @return array Validation errors
     */
    public function validateDatabaseParams(array $dbParams): array
    {
        $required = [
            'driver',
            'host',
            'name',
            'user',
        ];

        $messages = [];
        foreach ($required as $r) {
            if (!isset($dbParams[$r]) || empty($dbParams[$r])) {
                $messages[$r] = $this->translator->trans(
                    'mautic.core.value.required',
                    [],
                    'validators'
                );
            }
        }

        if (!isset($dbParams['port']) || (int) $dbParams['port'] <= 0) {
            $messages['port'] = $this->translator->trans(
                'mautic.install.database.port.invalid',
                [],
                'validators'
            );
        }

        if (!empty($dbParams['driver']) && !in_array($dbParams['driver'], DoctrineStep::getDriverKeys())) {
            $messages['driver'] = $this->translator->trans(
                'mautic.install.database.driver.invalid',
                ['%drivers%' => implode(', ', DoctrineStep::getDriverKeys())],
                'validators'
            );
        }

        return $messages;
    }

    /**
     * Create the database.
     */
    public function createDatabaseStep(StepInterface $step, array $dbParams): array
    {
        $messages = $this->validateDatabaseParams($dbParams);

        if (!empty($messages)) {
            return $messages;
        }

        // Check if connection works and/or create database if applicable
        $schemaHelper = new SchemaHelper($dbParams);

        try {
            $schemaHelper->testConnection();
            $schemaHelper->validateDatabaseVersion();

            if ($schemaHelper->createDatabase()) {
                $messages = $this->saveConfiguration($dbParams, $step, true);
                if (empty($messages)) {
                    return $messages;
                }
            }

            $messages['error'] = $this->translator->trans(
                'mautic.installer.error.creating.database',
                ['%name%' => $dbParams['name']],
                'flashes'
            );
        } catch (DatabaseVersionTooOldException $e) {
            $metadata = ThisRelease::getMetadata();

            $messages['error'] = $this->translator->trans(
                'mautic.installer.error.database.version',
                [
                    '%currentversion%'    => $e->getCurrentVersion(),
                    '%mysqlminversion%'   => $metadata->getMinSupportedMySqlVersion(),
                    '%mariadbminversion%' => $metadata->getMinSupportedMariaDbVersion(),
                ],
                'flashes'
            );
        } catch (\Exception $exception) {
            $messages['error'] = $this->translator->trans(
                'mautic.installer.error.connecting.database',
                ['%exception%' => $exception->getMessage()],
                'flashes'
            );
        }

        return $messages;
    }

    /**
     * Create the database schema.
     */
    public function createSchemaStep(array $dbParams): array
    {
        $schemaHelper  = new SchemaHelper($dbParams);
        $schemaHelper->setEntityManager($this->entityManager);

        $messages = [];
        try {
            if (!$schemaHelper->installSchema()) {
                $messages['error'] = $this->translator->trans(
                    'mautic.installer.error.no.metadata',
                    [],
                    'flashes');
            }
        } catch (\Exception $exception) {
            $messages['error'] = $this->translator->trans(
                'mautic.installer.error.installing.data',
                ['%exception%' => $exception->getMessage()],
                'flashes');
        }

        return $messages;
    }

    /**
     * Load the database fixtures in the database.
     */
    public function createFixturesStep(ContainerInterface $container): array
    {
        $messages = [];

        try {
            $this->installDatabaseFixtures($container);
        } catch (\Exception $exception) {
            $messages['error'] = $this->translator->trans(
                'mautic.installer.error.adding.fixtures',
                ['%exception%' => $exception->getMessage()],
                'flashes'
            );
        }

        return $messages;
    }

    /**
     * Installs data fixtures for the application.
     *
     * @throws \InvalidArgumentException
     */
    public function installDatabaseFixtures(ContainerInterface $container): void
    {
        $paths  = [dirname(__DIR__).'/InstallFixtures/ORM'];
        /** @phpstan-ignore-next-line */
        $loader = new ContainerAwareLoader($container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths)));
        }

        $purger = new ORMPurger($this->entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($this->entityManager, $purger);
        /*
         * FIXME entity manager does not load configuration if local.php just created by CLI install
         * [error] An error occurred while attempting to add default data
         * An exception occured in driver:
         * SQLSTATE[HY000] [1045] Access refused for user: ''@'@localhost' (mot de passe: NON)
         */
        $executor->execute($fixtures, true);
    }

    /**
     * Create the administrator user.
     */
    public function createAdminUserStep(array $data): array
    {
        $entityManager = $this->entityManager;

        //ensure the username and email are unique
        try {
            $existingUser = $entityManager->getRepository('MauticUserBundle:User')->find(1);
        } catch (\Exception $e) {
            $existingUser = null;
        }

        if (null != $existingUser) {
            $user = $existingUser;
        } else {
            $user = new User();
        }

        $required = [
            'firstname',
            'lastname',
            'username',
            'email',
            'password',
        ];

        $messages = [];
        foreach ($required as $r) {
            if (!isset($data[$r])) {
                $messages[$r] = $this->translator->trans(
                    'mautic.core.value.required',
                    [],
                    'validators'
                );
            }
        }

        if (!empty($messages)) {
            return $messages;
        }

        $validations  = [];

        $emailConstraint          = new Assert\Email();
        $emailConstraint->message = $this->translator->trans('mautic.core.email.required', [], 'validators');

        $passwordConstraint             = new Assert\Length(['min' => 6]);
        $passwordConstraint->minMessage = $this->translator->trans('mautic.install.password.minlength', [], 'validators');

        $validations[] = $this->validator->validate($data['email'], $emailConstraint);
        $validations[] = $this->validator->validate($data['password'], $passwordConstraint);

        $messages = [];
        foreach ($validations as $errors) {
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
        }

        if (!empty($messages)) {
            return $messages;
        }

        $encoder = $this->encoder;

        $user->setFirstName(InputHelper::clean($data['firstname']));
        $user->setLastName(InputHelper::clean($data['lastname']));
        $user->setUsername(InputHelper::clean($data['username']));
        $user->setEmail(InputHelper::email($data['email']));
        $user->setPassword($encoder->encodePassword($user, $data['password']));

        $adminRole = null;
        try {
            $adminRole = $entityManager->getReference('MauticUserBundle:Role', 1);
        } catch (\Exception $exception) {
            $messages['error'] = $this->translator->trans(
                'mautic.installer.error.getting.role',
                ['%exception%' => $exception->getMessage()],
                'flashes'
            );
        }

        if (!empty($adminRole)) {
            $user->setRole($adminRole);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Exception $exception) {
                $messages['error'] = $this->translator->trans(
                    'mautic.installer.error.creating.user',
                    ['%exception%' => $exception->getMessage()],
                    'flashes'
                );
            }
        }

        return $messages;
    }

    /**
     * Setup the email configuration.
     */
    public function setupEmailStep(StepInterface $step, array $data): array
    {
        $required = [
            'mailer_from_name',
            'mailer_from_email',
        ];

        $messages = [];
        foreach ($required as $r) {
            if (!isset($data[$r]) || empty($data[$r])) {
                $messages[$r] = $this->translator->trans(
                    'mautic.core.value.required',
                    [],
                    'validators'
                );
            }
        }

        if (!empty($messages)) {
            return $messages;
        }

        $emailConstraint          = new Assert\Email();
        $emailConstraint->message = $this->translator->trans('mautic.core.email.required',
            [],
            'validators'
        );

        $errors = $this->validator->validate(
            $data['mailer_from_email'],
            $emailConstraint
        );

        if (0 !== count($errors)) {
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $messages;
        }

        return $this->saveConfiguration($data, $step, true);
    }

    /**
     * Create the final configuration.
     */
    public function createFinalConfigStep(string $siteUrl): array
    {
        // Merge final things into the config, wipe the container, and we're done!
        $finalConfigVars = [
            'secret_key' => EncryptionHelper::generateKey(),
            'site_url'   => $siteUrl,
        ];

        return $this->saveConfiguration($finalConfigVars, null, true);
    }

    /**
     * Final migration step for install.
     */
    public function finalMigrationStep(): void
    {
        // Add database migrations up to this point since this is a fresh install (must be done at this point
        // after the cache has been rebuilt
        $input  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
        $output = new BufferedOutput();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run($input, $output);
    }
}
