<?php

namespace Page\Acceptance;

use AcceptanceTester;

class EmailsPage
{
    public static $URL = '/s/emails';

    public static $selectAllCheckbox           = '#customcheckbox-one0';
    public static $selectNewCategoryModal      = '#MauticSharedModal-label';

    public static $saveButton         = 'button.btn-save';

    public static $selectedActionsDropdown = '#app-content > div > div.panel.panel-default.bdr-t-wdh-0.mb-0 > div.page-list > div.table-responsive > table > thead > tr > th.col-actions > div > div > button > i';
    public static $changeCategoryAction    = "a[href='/s/emails/batch/categories/view']";
    public static $newCategoryDropdown     = '#email_batch_newCategory_chosen';
    public static $categoryBatchAddHidden  = 'email_batch_newCategory';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');.
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    /**
     * @var AcceptanceTester;
     */
    protected \AcceptanceTester $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public static function buildSelectorForCategory(int $number): string
    {
        return '#email_batch_add_chosen > div.chosen-drop > ul.chosen-results > li.active-result';
    }
}
