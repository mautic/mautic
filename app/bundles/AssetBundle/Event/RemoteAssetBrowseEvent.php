<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Event;

use Gaufrette\Adapter;
use Mautic\AddonBundle\Integration\AbstractIntegration;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class RemoteAssetBrowseEvent
 */
class RemoteAssetBrowseEvent extends CommonEvent
{

    /**
     * @var Adapter
     */
    private $connector;

    /**
     * @var AbstractIntegration
     */
    private $integration;

    /**
     * @param AbstractIntegration $integration
     */
    public function __construct(AbstractIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Adapter
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @return AbstractIntegration
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @param Adapter $connector
     *
     * @return $this
     */
    public function setConnector(Adapter $connector)
    {
        $this->connector = $connector;

        return $this;
    }
}
