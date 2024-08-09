<?php

namespace Mautic\EmailBundle\MonitoredEmail\Accessor;

class ConfigAccessor
{
    /**
     * @param mixed[] $config
     */
    public function __construct(
        private array $config
    ) {
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getProperty('imap_path');
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->getProperty('user');
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->getProperty('host');
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->getProperty('folder');
    }

    public function getKey(): string
    {
        return $this->getPath().'_'.$this->getUser();
    }

    public function isConfigured(): bool
    {
        return $this->getHost() && $this->getFolder();
    }

    /**
     * @return string|null
     */
    protected function getProperty($property)
    {
        return $this->config[$property] ?? null;
    }
}
