<?php

namespace Mautic\EmailBundle\MonitoredEmail\Accessor;

class ConfigAccessor
{
    private $config;

    /**
     * ConfigAccessor constructor.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
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

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->getPath().'_'.$this->getUser();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return $this->getHost() && $this->getFolder();
    }

    /**
     * @param $property
     *
     * @return string|null
     */
    protected function getProperty($property)
    {
        return isset($this->config[$property]) ? $this->config[$property] : null;
    }
}
