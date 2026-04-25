<?php

declare(strict_types=1);

namespace Page\Acceptance;

class MenuPage
{
    public static $URL              = '/s/dashboard';
    public static $POINTS           = '//a[@id="mautic_points_root"]//span[normalize-space()="Points"]';
    public static $MANAGE_GROUPS    = 'Manage Groups';
    public static $MANAGE_GROUPS_ID = '#mautic_point.group_index';
    public static $ACTIVE_NAV_GROUP = '.nav-group.last.active';
}
