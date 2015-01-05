<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Factory\MauticFactory;

class LogoutEvent extends Event
{

    private $factory;
    private $user;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
        $this->user    = $factory->getUser();
    }

    public function getFactory()
    {
        return $this->factory;
    }

    public function getUser()
    {
        return $this->user;
    }
}