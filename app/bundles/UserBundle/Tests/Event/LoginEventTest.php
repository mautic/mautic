<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Event;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;

class LoginEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUser()
    {
        $user  = $this->createMock(User::class);
        $event = new LoginEvent($user);

        $this->assertEquals($user, $event->getUser());
    }
}
