<?php

declare(strict_types=1);

namespace Page\Acceptance;

class DashboardWidgets
{
    public static $ADMIN_USER       = 'admin';
    public static $ADMIN_PASSWORD   = 'Maut1cR0cks!';
    public static $URL              = '/s/dashboard';

    public static $CONTACTS_COUNTER = 'Number of contacts';

    public static $DNC_COUNTER      = 'Number of DNC contacts';

    public static $CONTACTS_PER_SEGMENT = 'Number of contacts in specific segment';

    public static $TOOLBAR = '#toolbar';

    public static $FORM = 'form';

    public static $ADD_WIDGET = 'Add widget';

    public static $WIDGET_TYPE = '#widget_type_chosen';

    public static $WIDGET_TYPE_CHOSEN = '#widget_type_chosen .chosen-single';

    public static $WIDGET_TYPE_LIST = '//div[@id="widget_type_chosen"]//li';

    public static $SAVE_BUTTON_TEXT = '//button[contains(., "Save")]';

    public static $SAVE_BUTTON = 'button.btn-save';
}
