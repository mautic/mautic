<?php

$I = new AcceptanceTester($scenario);
$I->wantTo('Login to the website');
$I->amOnPage('/s/login');
$I->submitForm('.login-form', ['_username' => 'admin', '_password' => 'mautic']);
$I->seeCurrentUrlEquals('/s/dashboard');
