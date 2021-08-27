<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandHelper
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function runCommand(string $name, array $params = []): string
    {
        $params      = array_merge(['command' => $name], $params);
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input      = new ArrayInput($params);
        $output     = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }
}
