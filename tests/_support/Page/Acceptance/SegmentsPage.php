<?php

declare(strict_types=1);

namespace Page\Acceptance;

class SegmentsPage
{
    public static $URL                   = '/s/segments';
    public static $newButton             = '.list-toolbar > a#new > i';
    public static $segmentName           = '#leadlist_name';
    public static $saveAndCloseButton    = '#leadlist_buttons_save_toolbar';

    // Segment table name
    public static $tableName             = 'leadListTable';
    public static $detailsTab            = '//*[@href="#details"]';
    public static $filtersTab            = '//*[@href="#filters"]';
    public static $filtersDropdown       = '#available_segment_filters_chosen > a';
    public static $filtersSearchField    = '.chosen-search-input';

    public static $editButton            = '#toolbar > div.std-toolbar > a:nth-child(1)';
    public static $dropDown              = '#toolbar .std-toolbar > button';
    public static $delete                = '#toolbar > div.std-toolbar.open > ul > li:nth-child(5) > a';
    public static $ConfirmDelete         = 'button.btn.btn-danger';
}
