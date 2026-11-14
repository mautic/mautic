<?php

declare(strict_types=1);

namespace Acceptance;

use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\JsonWireCompat;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

final class HoldToConfirmCest
{
    private const BUTTON = '#reset-dismissed-tips';
    private const DISMISSED_TIPS = '["hold-confirm-tip"]';

    private ?string $dismissedTips = null;

    public function _before(\AcceptanceTester $I): void
    {
        $I->login();
        $I->amOnPage('/s/account');
        $I->waitForElementVisible(self::BUTTON, \AcceptanceTester::TIMEOUT);
        $this->dismissedTips = $I->executeJS('return localStorage.getItem("dismissedElements");');
        $I->executeJS('localStorage.setItem("dismissedElements", arguments[0]); Mautic.dismissedElements = JSON.parse(arguments[0]);', [self::DISMISSED_TIPS]);
        $I->executeJS('document.querySelector(arguments[0]).scrollIntoView({block: "center"});', [self::BUTTON]);
    }

    public function _after(\AcceptanceTester $I): void
    {
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $driver->action()->release()->perform();
        });
        $I->executeJS('if (arguments[0] === null) localStorage.removeItem("dismissedElements"); else localStorage.setItem("dismissedElements", arguments[0]); Mautic.dismissedElements = JSON.parse(arguments[0]) || [];', [$this->dismissedTips]);
    }

    public function shortClickDoesNotRestartTips(\AcceptanceTester $I): void
    {
        $I->click(self::BUTTON);
        $I->waitForElement('[data-component="hold-to-confirm"][data-state="idle"]', \AcceptanceTester::TIMEOUT);
        $I->assertSame(self::DISMISSED_TIPS, $I->executeJS('return localStorage.getItem("dismissedElements");'));
    }

    public function draggingOutsideCancelsCapturedPointer(\AcceptanceTester $I): void
    {
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $button = $driver->findElement(WebDriverBy::cssSelector(self::BUTTON));
            $driver->execute(DriverCommand::ACTIONS, [
                'actions' => [[
                    'type'       => 'pointer',
                    'id'         => 'mouse',
                    'parameters' => ['pointerType' => 'mouse'],
                    'actions'    => [
                        ['type' => 'pointerMove', 'origin' => [JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $button->getID()], 'x' => 0, 'y' => 0],
                        ['type' => 'pointerDown', 'button' => 0],
                        ['type' => 'pause', 'duration' => 200],
                        ['type' => 'pointerMove', 'origin' => 'pointer', 'x' => 0, 'y' => -$button->getSize()->getHeight() * 2],
                        ['type' => 'pause', 'duration' => 1500],
                        ['type' => 'pointerUp', 'button' => 0],
                    ],
                ]],
            ]);
        });
        $I->assertSame(self::DISMISSED_TIPS, $I->executeJS('return localStorage.getItem("dismissedElements");'));
        $I->seeElement('[data-component="hold-to-confirm"][data-state="idle"]');
    }

    public function secondTouchDoesNotCancelTheFirstHold(\AcceptanceTester $I): void
    {
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $button = $driver->findElement(WebDriverBy::cssSelector(self::BUTTON));
            $origin = [JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $button->getID()];
            $driver->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type'       => 'pointer',
                        'id'         => 'first-touch',
                        'parameters' => ['pointerType' => 'touch'],
                        'actions'    => [
                            ['type' => 'pointerMove', 'origin' => $origin, 'x' => -5, 'y' => 0],
                            ['type' => 'pointerDown', 'button' => 0],
                            ['type' => 'pause', 'duration' => 200],
                            ['type' => 'pause', 'duration' => 0],
                            ['type' => 'pause', 'duration' => 0],
                            ['type' => 'pause', 'duration' => 1500],
                            ['type' => 'pointerUp', 'button' => 0],
                        ],
                    ],
                    [
                        'type'       => 'pointer',
                        'id'         => 'second-touch',
                        'parameters' => ['pointerType' => 'touch'],
                        'actions'    => [
                            ['type' => 'pointerMove', 'origin' => $origin, 'x' => 5, 'y' => 0],
                            ['type' => 'pause', 'duration' => 0],
                            ['type' => 'pause', 'duration' => 200],
                            ['type' => 'pointerDown', 'button' => 0],
                            ['type' => 'pointerMove', 'origin' => 'pointer', 'x' => 0, 'y' => -$button->getSize()->getHeight() * 2],
                            ['type' => 'pause', 'duration' => 1500],
                            ['type' => 'pointerUp', 'button' => 0],
                        ],
                    ],
                ],
            ]);
        });
        $I->assertSame('[]', $I->executeJS('return localStorage.getItem("dismissedElements");'));
        $I->seeElement('[data-component="hold-to-confirm"][data-state="confirmed"]');
    }

    public function invalidSizeKeepsTheControlUsable(\AcceptanceTester $I): void
    {
        $I->executeJS('const button = document.querySelector(arguments[0]); Mautic.HoldToConfirmButton.get(button.closest("[data-component]")).update({size: "unknown"});', [self::BUTTON]);
        $I->seeElement(self::BUTTON.'[size="sm"]');
        $this->completedHoldRestartsTipsAndResets($I);
    }

    public function keyboardHoldRestartsTips(\AcceptanceTester $I): void
    {
        $I->executeJS('document.querySelector(arguments[0]).focus();', [self::BUTTON]);
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $driver->execute(DriverCommand::ACTIONS, [
                'actions' => [[
                    'type'    => 'key',
                    'id'      => 'keyboard',
                    'actions' => [
                        ['type' => 'keyDown', 'value' => WebDriverKeys::SPACE],
                        ['type' => 'pause', 'duration' => 1500],
                        ['type' => 'keyUp', 'value' => WebDriverKeys::SPACE],
                    ],
                ]],
            ]);
        });
        $I->assertSame('[]', $I->executeJS('return localStorage.getItem("dismissedElements");'));
        $I->assertSame('reset-dismissed-tips', $I->executeJS('return document.activeElement.id;'));
    }

    public function completedHoldRestartsTipsAndResets(\AcceptanceTester $I): void
    {
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $driver->action()->clickAndHold($driver->findElement(WebDriverBy::cssSelector(self::BUTTON)))->perform();
        });
        $I->waitForJS('return localStorage.getItem("dismissedElements") === "[]";', \AcceptanceTester::TIMEOUT);
        $I->seeElement('[data-component="hold-to-confirm"][data-state="confirmed"]');
        $I->assertNotEmpty($I->grabTextFrom('.hold-to-confirm__status'));
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $driver->action()->release()->perform();
        });
        $I->waitForElement('[data-component="hold-to-confirm"][data-state="idle"]', \AcceptanceTester::TIMEOUT);
        $I->assertSame([], $I->executeJS('return Mautic.dismissedElements;'));
    }
}
