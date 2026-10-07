<?php

namespace Mautic\CoreBundle\Tests\Unit\Command\src;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockInterface;

#[AsCommand(
    name: 'mautic:fake:command'
)]
final class FakeModeratedCommand extends ModeratedCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkRunStatus($input, $output);

        return Command::SUCCESS;
    }

    public function forceCompleteRun(): void
    {
        $this->completeRun();
    }

    public function setLock(?LockInterface $lock = null): void
    {
        $reflection  = new \ReflectionClass($this);
        $parentClass = $reflection->getParentClass();

        if (!$parentClass->hasProperty('lock')) {
            throw new \RuntimeException("The 'lock' property does not exist in the parent class.");
        }

        $property = $parentClass->getProperty('lock');
        $property->setValue($this, $lock);
    }

    public function setLockFile(string $lockFilePath): void
    {
        $this->lockFile = $lockFilePath;
    }
}
