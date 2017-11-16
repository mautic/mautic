<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Accessor;

class ConfigAccessor
{
    protected $config;

    /**
     * ConfigAccessor constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->getProperty('imap_path');
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->getProperty('user');
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->getProperty('host');
    }

    /**
     * @return mixed
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
     * @return mixed|null
     */
    protected function getProperty($property)
    {
        return isset($this->config[$property]) ? $this->config[$property] : null;
    }
}
