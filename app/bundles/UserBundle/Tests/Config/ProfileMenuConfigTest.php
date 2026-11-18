<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Config;

use PHPUnit\Framework\TestCase;

final class ProfileMenuConfigTest extends TestCase
{
    public function testProfileActionsAreMenuItemsInDisplayOrder(): void
    {
        $config = require dirname(__DIR__, 2).'/Config/config.php';
        $items  = $config['menu']['profile']['items'];

        $this->assertSame('mautic_user_account', $items['mautic.user.account.settings']['route']);
        $this->assertSame(400, $items['mautic.user.account.settings']['priority']);

        $invite = $items['mautic.user.profile.invite'];
        $this->assertSame('mautic_user_index', $invite['route']);
        $this->assertSame(['open' => 'invite'], $invite['routeParameters']);
        $this->assertSame('user:users:create', $invite['access']);
        $this->assertSame(300, $invite['priority']);

        $logout = $items['mautic.user.auth.logout'];
        $this->assertSame('mautic_user_logout', $logout['route']);
        $this->assertSame(200, $logout['priority']);
        $this->assertSame('_self', $logout['linkAttributes']['target']);
    }
}
