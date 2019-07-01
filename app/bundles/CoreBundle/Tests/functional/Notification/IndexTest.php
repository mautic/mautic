<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\functional\Notification;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;

class IndexTest extends MauticMysqlTestCase
{
    /**
     * @test
     */
    public function it_shows_notifications_on_index()
    {
        // The default logged in user is 'admin', is there not a function/trait
        // to get the default admin user for tests?
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['username' => 'admin']);

        $notification = new Notification();
        $notification->setMessage('Test Notification Message!');
        $notification->setDateAdded(new \DateTime());
        $notification->setUser($user);

        $this->em->persist($notification);
        $this->em->flush();
        $this->em->refresh($notification);

        $this->client->request('GET', 's/account/notifications');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains($notification->getMessage(), $this->client->getResponse()->getContent());
        // Asserting the message exists isn't really reliable, since the
        // notification would be on every page because of the widget, so we
        // test by making sure the date is visible on the table.
        $this->assertContains($notification->getDateAdded()->format('Y-m-d H:i:s'), $this->client->getResponse()->getContent());
    }
}
