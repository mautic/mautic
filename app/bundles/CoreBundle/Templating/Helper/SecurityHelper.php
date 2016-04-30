<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class SecurityHelper
 */
class SecurityHelper extends Helper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'security';
    }

    /**
     * Helper function to check if the logged in user has access to an entity
     *
     * @param $ownPermission
     * @param $otherPermission
     * @param $ownerId
     *
     * @return bool
     */
    public function hasEntityAccess($ownPermission, $otherPermission, $ownerId)
    {
        return $this->factory->getSecurity()->hasEntityAccess($ownPermission, $otherPermission, $ownerId);
    }

    /**
     * @param $permission
     *
     * @return mixed
     */
    public function isGranted($permission)
    {
        return $this->factory->getSecurity()->isGranted($permission);
    }
}
