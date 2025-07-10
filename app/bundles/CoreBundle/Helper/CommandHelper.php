<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandHelper
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    /**
     * @param array<int|string> $params
     */
    public function runCommand(string $name, array $params = []): CommandResponse
    {
        $params      = array_merge(['command' => $name], $params);
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input      = new ArrayInput($params);
        $output     = new BufferedOutput();
        $statusCode = $application->run($input, $output);
        $message    = $output->fetch();

        return new CommandResponse($statusCode, $message);
    }
}
