<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Integration;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Entity\ApplicationIntegration;
use Mautic\MapperBundle\Entity\ApplicationIntegrationRepository;
use Mautic\MapperBundle\Entity\ApplicationObjectMapper;
use Mautic\MapperBundle\Entity\ApplicationObjectMapperRepository;
use Mautic\MapperBundle\Helper\ApiHelper;

abstract class AbstractMapper
{
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Determines what priority the network should have against the other networks
     *
     * @return mixed
     */
    public function getPriority()
    {
        return 9999;
    }

    /**
     * Return Application Link
     *
     * @return string
     */
    public function getSaveLink()
    {
        return $this->factory->getRouter()->generate('mautic_mapper_save',array('application' => $this->getAppAlias()));
    }

    /**
     * Return Application Link
     *
     * @return string
     */
    public function getClientLink()
    {
        return $this->factory->getRouter()->generate('mautic_mapper_client_index',array('application' => $this->getAppAlias()));
    }

    /**
     * Return callback link
     *
     * @return string
     */
    public function getCallbackLink()
    {
        return $this->factory->getRouter()->generate('mautic_mapper_callback',array('application' => $this->getAppAlias()), true);
    }

    /**
     * Returns the name of the application
     *
     * @return string
     */
    abstract public function getAppAlias();

    /**
     * Returns the name of the application
     *
     * @return string
     */
    abstract public function getAppName();

    /**
     * Returns the image source from application
     *
     * @return string
     */
    abstract public function getImage();

    /**
     * Returns supported objects from this integration
     *
     * @return array
     */
    abstract public function getSupportedObjects();

    /**
     * Returns settings array for library
     *
     * @return string
     */
    abstract public function getSettings();

    /**
     * Return Mautic fields according with supported object name
     *
     * @param $objectName
     */
    abstract public function getMauticObject($objectName);

    /**
     * Return Api Object fields according with supported object name
     *
     * @param $objectName
     */
    abstract public function getApiObject($objectName);

    /**
     * Return Api Object fields according with supported object name
     *
     * @param $objectName
     */
    abstract public function getObjectOptions($objectName, $response);

    /**
     * Return Api Authentication Object
     *
     * @return mixed
     */
    public function getApiAuth()
    {
        return ApiHelper::getApiAuth($this->getAppAlias(), $this);
    }
}