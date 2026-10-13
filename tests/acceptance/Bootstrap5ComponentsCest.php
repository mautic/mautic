<?php

declare(strict_types=1);

namespace Acceptance;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class Bootstrap5ComponentsCest
{
    public function _before(\AcceptanceTester $tester): void
    {
        $root = dirname(__DIR__, 2);
        $tester->amOnPage('/tests/_data/mautic-script-split.html');
        $tester->executeJS('window.bootstrapErrors = []; window.addEventListener("error", event => window.bootstrapErrors.push(event.message));');
        $css = getenv('BOOTSTRAP5_VALIDATION_CSS') ?: $root.'/vendor/twbs/bootstrap/dist/css/bootstrap.css';
        $tester->executeJS('const style = document.createElement("style"); style.textContent = '.json_encode(file_get_contents($css)).'; document.head.appendChild(style);');
        $tester->executeJS((string) file_get_contents($root.'/node_modules/jquery/dist/jquery.js'));
        $tester->executeJS('window.mQuery = window.jQuery; window.MauticVars = {}; window.Mautic = {translate: key => key};');
        $tester->executeJS((string) file_get_contents($root.'/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.js'));
        $tester->executeJS((string) file_get_contents($root.'/app/bundles/CoreBundle/Assets/js/9.modals.js'));
        $tester->executeJS((string) file_get_contents($root.'/app/bundles/CoreBundle/Assets/js/1a.content.js'));
        $tester->executeJS('Mautic.onPageLoad = () => {}; Mautic.onPageUnload = () => {}; Mautic.stopIconSpinPostEvent = () => {};');
        $twig = new Environment(new FilesystemLoader($root.'/app/bundles/CoreBundle/Resources/views/Helper'));
        $twig->addExtension(new TranslationExtension());
        $tabs = $twig->render('nav_tabs.html.twig', [
            'tabs' => [
                ['id' => 'first', 'title' => 'first', 'content' => 'first content'],
                ['id' => 'second', 'title' => 'second', 'content' => 'second content'],
            ],
        ]);
        $html = $tabs.(string) file_get_contents($root.'/tests/_data/bootstrap5-components.html');
        $tester->executeJS('document.body.innerHTML = '.json_encode($html).';');
        $tester->executeJS('window.modalEvents = []; mQuery("#testModal").on("show.bs.modal shown.bs.modal hide.bs.modal hidden.bs.modal", event => modalEvents.push(event.type));');
    }

    public function _after(\AcceptanceTester $tester): void
    {
        $tester->assertSame([], $tester->executeJS('return window.bootstrapErrors;'));
    }

    public function closesModalAfterImmediateAjaxResponse(\AcceptanceTester $tester): void
    {
        $tester->executeJS('bootstrap.Modal.getOrCreateInstance("#testModal").show(); Mautic.processModalContent({closeModal: true}, "#testModal");');
        $tester->waitForJS('return modalEvents.includes("hidden")', 5);
        $tester->assertSame(['show', 'shown', 'hide', 'hidden'], $tester->executeJS('return modalEvents;'));
        $tester->assertSame(0, $tester->executeJS('return document.querySelectorAll(".modal-backdrop").length;'));
        $tester->assertSame('none', $tester->executeJS('return getComputedStyle(document.querySelector("#testModal")).display;'));
        $tester->assertFalse($tester->executeJS('return document.body.classList.contains("modal-open");'));
    }

    public function reusesStaticAndDismissibleModals(\AcceptanceTester $tester): void
    {
        $tester->executeJS('Mautic.configureModal(document.querySelector("#testModal"), {keyboard: false, backdrop: "static"}).show();');
        $tester->waitForJS('return modalEvents.includes("shown")', 5);
        $tester->executeJS('document.querySelector("#testModal").dispatchEvent(new KeyboardEvent("keydown", {key: "Escape", bubbles: true}));');
        $tester->assertTrue($tester->executeJS('return document.querySelector("#testModal").classList.contains("show");'));
        $tester->executeJS('Mautic.configureModal(document.querySelector("#testModal"), {keyboard: true, backdrop: true}); document.querySelector("#testModal").dispatchEvent(new KeyboardEvent("keydown", {key: "Escape", bubbles: true}));');
        $tester->waitForJS('return modalEvents.includes("hidden")', 5);
        $tester->assertSame(0, $tester->executeJS('return document.querySelectorAll(".modal-backdrop").length;'));
    }

    public function switchesRenderedFadedTabsWithNativeKeyboardSupport(\AcceptanceTester $tester): void
    {
        $tester->click('a[href="#second"]');
        $tester->waitForJS('return document.querySelector("#second").classList.contains("show") && getComputedStyle(document.querySelector("#second")).opacity === "1"', 5);
        $tester->assertSame(1, $tester->executeJS('return document.querySelectorAll(".tab-pane.active").length;'));
        $tester->assertSame('none', $tester->executeJS('return getComputedStyle(document.querySelector("#first")).display;'));
        $tester->executeJS('document.querySelector("a[href=\"#second\"]").dispatchEvent(new KeyboardEvent("keydown", {key: "ArrowLeft", bubbles: true}));');
        $tester->waitForJS('return document.querySelector("#first").classList.contains("show")', 5);
        $tester->assertSame('true', $tester->executeJS('return document.querySelector("a[href=\"#first\"]").getAttribute("aria-selected");'));
    }

    public function refreshesTooltipThroughPublicContentApi(\AcceptanceTester $tester): void
    {
        $tester->executeJS('window.originalTooltip = bootstrap.Tooltip.getOrCreateInstance(document.querySelector("#scopeTrigger"), {animation: false}); Mautic.updateSearchScopeMobileTrigger(document.querySelector("#scope")); document.querySelector("#scopeTrigger").focus();');
        $tester->waitForJS('return document.querySelector(".tooltip-inner")?.textContent === "Filtering by: First"', 5);
        $tester->executeJS('document.querySelector("#scope").value = "second"; Mautic.updateSearchScopeMobileTrigger(document.querySelector("#scope"));');
        $tester->waitForJS('return document.querySelector(".tooltip-inner")?.textContent === "Filtering by: Second"', 5);
        $tester->assertTrue($tester->executeJS('return originalTooltip === bootstrap.Tooltip.getInstance(document.querySelector("#scopeTrigger"));'));
        $tester->assertFalse($tester->executeJS('return document.querySelector("#scopeTrigger").hasAttribute("title");'));
        $tester->executeJS('originalTooltip.dispose();');
        $tester->assertSame(0, $tester->executeJS('return document.querySelectorAll(".tooltip").length;'));
    }

    public function usesNativeCollapseAndDropdownDataApis(\AcceptanceTester $tester): void
    {
        $tester->click('#collapseTrigger');
        $tester->waitForJS('return document.querySelector("#testCollapse").classList.contains("show")', 5);
        $tester->assertSame('true', $tester->executeJS('return document.querySelector("#collapseTrigger").getAttribute("aria-expanded");'));
        $tester->click('#dropdownTrigger');
        $tester->waitForJS('return document.querySelector("#dropdownMenu").classList.contains("show")', 5);
        $tester->click('#outside');
        $tester->waitForJS('return !document.querySelector("#dropdownMenu").classList.contains("show")', 5);
        $tester->assertNull($tester->executeJS('return window.MauticBootstrapCompatibility || null;'));
    }
}
