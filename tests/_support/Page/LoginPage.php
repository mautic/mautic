<?php
namespace Page;

class LoginPage
{
    // include url of current page
    public static $URL = '/s/login';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";.
     */
    public static $username = ".//*[@id='username']";
    public static $password = ".//*[@id='password']";
    public static $login    = ".//*[@id='main']/div/div[1]/div/div/div/form/button";
    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }
    /**
     * @var \ContactsTester;
     */
    protected $contactsTester;

    public function __construct(\ContactsTester $I)
    {
        $this->contactsTester = $I;
    }

}
