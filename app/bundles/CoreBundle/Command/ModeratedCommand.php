<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;

abstract class ModeratedCommand extends Command
{
    public const MODE_PID   = 'pid';

    public const MODE_FLOCK = 'flock';

    public const MODE_REDIS = 'redis';

    /**
     * @deprecated Symfony 4 Removed LockHandler and the replacement is the lock from the Lock component so there is no need for something custom
     */
    public const MODE_LOCK = 'file_lock';

    protected $moderationKey;

    protected $moderationMode;

    protected $runDirectory;

    protected $lockExpiration;

    private array $locks = [];

    private array $lockFiles = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        protected PathsHelper $pathsHelper,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct();
    }

    /**
     * Set moderation options.
     */
    protected function configure(): void
    {
        $this
            ->addOption('--bypass-locking', null, InputOption::VALUE_NONE, 'Bypass locking.')
            ->addOption(
                '--timeout',
                '-t',
                InputOption::VALUE_REQUIRED,
                'If getmypid() is disabled on this system, lock files will be used. This option will assume the process is dead after the specified number of seconds and will execute anyway. This is disabled by default.'
            )
            ->addOption(
                '--lock_mode',
                '-x',
                InputOption::VALUE_REQUIRED,
                'Allowed value are "pid", "flock" or redis. By default, lock will try with pid, if not available will use file system',
                self::MODE_PID
            )
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Deprecated; use --bypass-locking instead.');
    }

    protected function checkRunStatus(InputInterface $input, OutputInterface $output, $moderationKey = ''): bool
    {
        // Bypass locking
        if ((bool) $input->getOption('bypass-locking') || (bool) $input->getOption('force')) {
            return true;
        }

        $this->output = $output;

        $this->lockExpiration = $input->getOption('timeout');
        if (null !== $this->lockExpiration) {
            $this->lockExpiration = (float) $this->lockExpiration;
        }

        $this->moderationMode = $input->getOption('lock_mode');
        if (self::MODE_LOCK === $this->moderationMode) {
            // File lock is deprecated in favor of Symfony's Lock component's lock
            $this->moderationMode = 'flock';
        }
        if (!in_array($this->moderationMode, [self::MODE_PID, self::MODE_FLOCK, self::MODE_REDIS])) {
            $output->writeln('<error>Unknown locking method specified.</error>');

            return false;
        }

        // Allow multiple runs of the same command if executing different IDs, etc
        $this->moderationKey = $this->getName().$moderationKey;
        if (in_array($this->moderationMode, [self::MODE_PID, self::MODE_FLOCK])) {
            // Setup the run directory for lock/pid files
            $this->runDirectory = $this->pathsHelper->getSystemPath('cache').'/../run';
            if (!file_exists($this->runDirectory) && !@mkdir($this->runDirectory)) {
                // This needs to throw an exception in order to not silently fail when there is an issue
                throw new \RuntimeException($this->runDirectory.' could not be created.');
            }
        }

        // Check if the command is currently running
        if (!$this->checkStatus($this->moderationKey)) {
            $output->writeln('<error>Script in progress. Can force execution by using --bypass-locking.</error>');

            return false;
        }

        return true;
    }

    /**
     * Complete this run.
     */
    protected function completeRun(): void
    {
        foreach (array_reverse($this->locks) as $lock) {
            $lock->release();
        }
        $this->locks = [];

        // Attempt to keep things tidy
        foreach ($this->lockFiles as $lockFile) {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
        $this->lockFiles = [];
    }

    /**
     * @param array<int, string> $moderationKeys
     */
    protected function checkAdditionalRunStatus(InputInterface $input, OutputInterface $output, array $moderationKeys, string $conflictMessage): bool
    {
        if ((bool) $input->getOption('bypass-locking') || (bool) $input->getOption('force')) {
            return true;
        }

        $lockCount     = count($this->locks);
        $lockFileCount = count($this->lockFiles);
        foreach (array_unique($moderationKeys) as $moderationKey) {
            if (!$this->checkStatus($this->getName().$moderationKey, false)) {
                $this->releaseLocksFrom($lockCount, $lockFileCount);
                $output->writeln('<error>'.$conflictMessage.'</error>');

                return false;
            }
        }

        return true;
    }

    private function releaseLocksFrom(int $lockCount, int $lockFileCount): void
    {
        while (count($this->locks) > $lockCount) {
            array_pop($this->locks)->release();
        }

        while (count($this->lockFiles) > $lockFileCount) {
            $lockFile = array_pop($this->lockFiles);
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }

    private function checkStatus(string $moderationKey, bool $announce = true): bool
    {
        if (self::MODE_PID === $this->moderationMode && $this->isPidSupported()) {
            return $this->checkPid($moderationKey, $announce);
        }

        return $this->checkFlock($moderationKey);
    }

    private function checkPid(string $moderationKey, bool $announce): bool
    {
        $lockFile = sprintf(
            '%s/sf.%s.%s.lock',
            $this->runDirectory,
            preg_replace('/[^a-z0-9\._-]+/i', '-', $moderationKey),
            hash('sha256', $moderationKey)
        );

        // Check if the PID is still running
        $fp = fopen($lockFile, 'c+');
        if (!flock($fp, LOCK_EX)) {
            $this->output->writeln("<error>Failed to lock {$lockFile}.</error>");

            return false;
        }

        $pid = fgets($fp, 8192);
        if ($pid && posix_getpgid((int) $pid)) {
            if ($announce) {
                $this->output->writeln('<info>Script with pid '.$pid.' in progress.</info>');
            }

            flock($fp, LOCK_UN);
            fclose($fp);

            return false;
        }

        // Write current PID to lock file
        ftruncate($fp, 0);
        rewind($fp);

        fwrite($fp, (string) getmypid());
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);
        $this->lockFiles[] = $lockFile;

        return true;
    }

    private function checkFlock(string $moderationKey): bool
    {
        switch ($this->moderationMode) {
            case self::MODE_REDIS:
                $cacheAdapterConfig = $this->coreParametersHelper->get('cache_adapter_redis');
                $redisDsn           = $cacheAdapterConfig['dsn'] ?? null;
                $redisOptions       = $cacheAdapterConfig['options'] ?? [];
                $redis              = RedisAdapter::createConnection($redisDsn, $redisOptions);
                $store              = new RedisStore($redis, $this->lockExpiration ?? 3600);
                break;
            default:
                $store = new FlockStore($this->runDirectory);
        }

        $factory = new LockFactory($store);
        $lock    = $factory->createLock($moderationKey, $this->lockExpiration);

        if (!$lock->acquire()) {
            return false;
        }

        $this->locks[] = $lock;

        return true;
    }

    public function isPidSupported(): bool
    {
        // getmypid may be disabled and posix_getpgid is not available on Windows machines
        if (!function_exists('getmypid') || !function_exists('posix_getpgid')) {
            return false;
        }

        $disabled = explode(',', ini_get('disable_functions'));

        return !in_array('getmypid', $disabled) && !in_array('posix_getpgid', $disabled);
    }
}
