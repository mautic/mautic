<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerInterface;

class AppTestKernel extends AppKernel
{
    private bool $isTestContainerSet = false;

    /**
     * {@inheritdoc}
     */
    protected function isInstalled(): bool
    {
        return true;
    }

    public function getCacheDir(): string
    {
        if ($dir = getenv('TEST_CACHE_DIR')) {
            return dirname(__DIR__).'/'.$dir;
        }

        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if ($dir = getenv('TEST_LOG_DIR')) {
            return dirname(__DIR__).'/'.$dir;
        }

        return parent::getLogDir();
    }

    /**
     * @throws Exception
     */
    public function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            $this->boot();
        }

        if ($this->isTestContainerSet) {
            return $this->container;
        }

        $this->isTestContainerSet = true;

        $testContainer = $this->container->get('test.service_container');
        $testContainer->setPublicContainer($this->container);

        return $this->container;
    }
}
