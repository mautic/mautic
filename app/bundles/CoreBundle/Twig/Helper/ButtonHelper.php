<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ButtonHelper
{
    /**
     * List dropdown actions.
     */
    public const LOCATION_LIST_ACTIONS = 'list_actions';

    /**
     * Toolbar actions.
     */
    public const LOCATION_TOOLBAR_ACTIONS = 'toolbar_actions';

    /**
     * Page actions.
     */
    public const LOCATION_PAGE_ACTIONS = 'page_actions';

    /**
     * Navbar actions.
     */
    public const LOCATION_NAVBAR = 'navbar_actions';

    /**
     * Bulk actions.
     */
    public const LOCATION_BULK_ACTIONS = 'bulk_actions';

    /**
     * Buttons are displayed in group and/or dropdown depending on button count.
     */
    public const TYPE_BUTTON_DROPDOWN = 'button-dropdown';

    /**
     * Buttons are displayed in dropdown depending on button count.
     */
    public const TYPE_DROPDOWN = 'dropdown';

    /**
     * Buttons are grouped together.
     */
    public const TYPE_GROUP = 'group';

    /**
     * Location of the buttons.
     *
     * @var string
     */
    private $location;

    /**
     * @var string|null
     */
    private $wrapOpeningTag;

    /**
     * @var string|null
     */
    private $wrapClosingTag;

    private string $groupType = self::TYPE_GROUP;

    /**
     * @var string|null
     */
    private $menuLink;

    /**
     * @var array<array<string,mixed>>
     */
    private $buttons = [];

    /**
     * @var int
     */
    private $buttonCount = 0;

    private bool $buttonsFetched = false;

    private ?\Symfony\Component\HttpFoundation\Request $request = null;

    /**
     * @var mixed
     */
    private $item;

    /**
     * @var int
     */
    private $listMarker = 3;

    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param array<array<string,mixed>> $buttons
     *
     * @return $this
     */
    public function addButtons(array $buttons)
    {
        $this->buttonCount += count($buttons);
        $this->buttons = array_merge($this->buttons, $buttons);

        return $this;
    }

    /**
     * @param array<string,mixed> $button
     *
     * @return $this
     */
    public function addButton(array $button)
    {
        $this->buttons[] = $button;
        ++$this->buttonCount;

        return $this;
    }

    /**
     * @param string|null $wrapOpeningTag
     * @param string|null $wrapClosingTag
     *
     * @return $this
     */
    public function setWrappingTags($wrapOpeningTag, $wrapClosingTag)
    {
        $this->wrapOpeningTag = $wrapOpeningTag;
        $this->wrapClosingTag = $wrapClosingTag;

        return $this;
    }

    /**
     * @param string $groupType
     *
     * @return $this
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;

        return $this;
    }

    /**
     * @param string|null $menuLink
     *
     * @return $this
     */
    public function setMenuLink($menuLink)
    {
        $this->menuLink = $menuLink;

        return $this;
    }

    /**
     * @return int
     */
    public function getButtonCount()
    {
        return $this->buttonCount;
    }

    /**
     * @param string $dropdownHtml
     * @param string $closingDropdownHtml
     */
    public function renderButtons($dropdownHtml = '', $closingDropdownHtml = ''): string
    {
        $this->fetchCustomButtons();
        $this->orderButtons();

        $content              = '';
        $dropdownHtmlAppended = false;
        if (!empty($this->buttons)) {
            $buttonCount = 0;

            foreach ($this->buttons as $button) {
                ++$buttonCount;

                $content .= $this->buildButton($button, $buttonCount);

                $nextButton = $buttonCount + 1;
                if (self::TYPE_BUTTON_DROPDOWN == $this->groupType && $nextButton === $this->listMarker && $buttonCount !== $this->buttonCount) {
                    $content .= $dropdownHtml;
                    $dropdownHtmlAppended = true;
                }
            }
        }

        if ($dropdownHtmlAppended) {
            $content .= $closingDropdownHtml;
        }

        return $content;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     *
     * @return ButtonHelper
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Reset the buttons.
     *
     * @param string $buttonCount
     * @param string $groupType
     *
     * @return $this
     */
    public function reset(Request $request, $buttonCount, $groupType = self::TYPE_GROUP, $item = null)
    {
        // @escopecz: I think there is a possible bug here
        $this->location       = $buttonCount;
        $this->groupType      = $groupType;
        $this->buttonCount    = 0;
        $this->buttons        = [];
        $this->buttonsFetched = false;
        $this->request        = $request;
        $this->item           = $item;
        $this->listMarker     = 3;
        $this->wrapOpeningTag = null;
        $this->wrapClosingTag = null;

        return $this;
    }

    public function getName(): string
    {
        return 'buttons';
    }

    /**
     * @param array<string,mixed> $button
     * @param int                 $buttonCount
     */
    private function buildButton($button, $buttonCount = 0): string
    {
        $buttons = '';

        // Wrap links in a tag
        if (self::TYPE_DROPDOWN == $this->groupType || (self::TYPE_BUTTON_DROPDOWN == $this->groupType && $buttonCount >= $this->listMarker)) {
            $this->wrapOpeningTag = "<li>\n";
            $this->wrapClosingTag = "</li>\n";
        }

        if (!isset($button['attr'])) {
            $button['attr'] = [];
        }

        if (self::TYPE_GROUP == $this->groupType || (self::TYPE_BUTTON_DROPDOWN == $this->groupType && $buttonCount < $this->listMarker)) {
            $this->addButtonClasses($button);
        } elseif (in_array($this->groupType, [self::TYPE_BUTTON_DROPDOWN, self::TYPE_DROPDOWN])) {
            $this->removeButtonClasses($button);
        }

        if (isset($button['confirm'])) {
            $button['confirm']['btnTextAttr'] = $this->generateTextAttributes($button);
            $buttons .= $this->wrapOpeningTag.$this->twig->render('@MauticCore/Helper/confirm.html.twig', $button['confirm']).
                "{$this->wrapClosingTag}\n";
        } else {
            $attr = $this->menuLink;

            if (!isset($button['attr']['data-toggle'])) {
                $button['attr']['data-toggle'] = 'ajax';
            }

            $btnTextAttr = $this->generateTextAttributes($button);
            $tooltip     = $this->generateTooltipAttributes($button);

            foreach ($button['attr'] as $k => $v) {
                $attr .= " $k=".'"'.$v.'"';
            }

            $buttonContent = (isset($button['iconClass'])) ? '<i class="'.$button['iconClass'].'"></i> ' : '';
            if (!empty($button['btnText'])) {
                $buttonContent .= '<span'.$btnTextAttr.'>'.$this->translator->trans($button['btnText']).'</span>';
            }
            $buttons .= "{$this->wrapOpeningTag}<a{$attr}><span{$tooltip}>{$buttonContent}</span></a>{$this->wrapClosingTag}\n";
        }

        return $buttons;
    }

    /**
     * @return $this
     */
    private function fetchCustomButtons()
    {
        if (!$this->buttonsFetched && $this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS)) {
            $event = $this->dispatcher->dispatch(
                new CustomButtonEvent($this->location, $this->request, $this->buttons, $this->item),
                CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS
            );
            $this->buttonsFetched = true;
            $this->buttons        = $event->getButtons();
            $this->buttonCount    = count($this->buttons);
        }

        return $this;
    }

    /**
     * Order buttons by priority.
     */
    private function orderButtons(): void
    {
        foreach ($this->buttons as $key => $button) {
            $this->validatePriority($this->buttons[$key]);
        }

        uasort(
            $this->buttons,
            function ($a, $b): int {
                $ap = (isset($a['priority']) ? (int) $a['priority'] : 0);
                $bp = (isset($b['priority']) ? (int) $b['priority'] : 0);

                if ($ap == $bp) {
                    $aText = $bText = '';

                    // Sort alphabetically
                    if (isset($a['confirm']) && isset($a['confirm']['btnText'])) {
                        $aText = $a['confirm']['btnText'];
                    } elseif (isset($a['btnText'])) {
                        $aText = $a['btnText'];
                    }

                    if (isset($b['confirm']) && isset($b['confirm']['btnText'])) {
                        $bText = $b['confirm']['btnText'];
                    } elseif (isset($b['btnText'])) {
                        $bText = $b['btnText'];
                    }

                    return strcasecmp($aText, $bText);
                }

                return ($ap > $bp) ? -1 : 1;
            }
        );

        if (self::TYPE_BUTTON_DROPDOWN == $this->groupType) {
            // Find the start of the non-primary buttons
            $counter = 0;
            foreach ($this->buttons as $button) {
                ++$counter;

                if (empty($button['primary'])) {
                    $this->listMarker = $counter;

                    break;
                }
            }

            if ($this->listMarker <= 1 && $this->buttonCount) {
                // Show at least one button
                $this->listMarker = 2;
            }
        }
    }

    /**
     * @param array<string,mixed> $button
     */
    private function validatePriority(&$button): void
    {
        if (!empty($button['primary'])) {
            if (!isset($button['priority']) || $button['priority'] < 200) {
                $button['priority'] = 215;
            }
        } elseif (!isset($button['priority'])) {
            $button['priority'] = 0;
        }
    }

    /**
     * @param array<string,mixed> $button
     */
    private function generateTextAttributes(&$button): string
    {
        $btnTextAttr = '';
        if (isset($button['btnTextAttr'])) {
            foreach ($button['btnTextAttr'] as $k => $v) {
                $btnTextAttr .= " $k=".'"'.$v.'"';
            }
        }

        if (isset($button['btnTextClass'])) {
            $btnTextAttr .= ' class="'.$button['btnTextClass'].'"';
            unset($button['btnTextClass']);
        }

        return $btnTextAttr;
    }

    /**
     * @param array<string,mixed> $button
     */
    private function generateTooltipAttributes($button): string
    {
        $tooltip = '';
        if (isset($button['tooltip'])) {
            $tooltip .= ' data-toggle="tooltip"';
            if (is_array($button['tooltip'])) {
                foreach ($button['tooltip'] as $k => $v) {
                    if ('title' == $k) {
                        $v = $this->translator->trans($v);
                    }
                    $tooltip .= " $k=".'"'.$v.'"';
                }
            } else {
                $tooltip .= ' title="'.$this->translator->trans($button['tooltip']).'" data-placement="left"';
            }
        }

        return $tooltip;
    }

    /**
     * @param array<string,mixed> $button
     */
    private function addMobileResponsiveClasses(&$button): void
    {
        if (isset($button['confirm'])) {
            $change = &$button['confirm'];
        } else {
            $change = &$button;
        }

        // Default all text to hidden for mobile
        if (isset($change['btnTextClass'])) {
            $change['btnTextClass'] .= ' hidden-xs hidden-sm';
        } else {
            $change['btnTextClass'] = 'hidden-xs hidden-sm';
        }
    }

    /**
     * @param array<string,mixed> $button
     */
    private function addButtonClasses(&$button): void
    {
        if (isset($button['confirm'])) {
            $addTo = &$button['confirm'];
        } else {
            $addTo = &$button;
        }

        if (!empty($addTo['btnClass'])) {
            $addTo['attr']['class'] = $addTo['btnClass'];
        } elseif (!isset($button['attr']['class'])) {
            $addTo['attr']['class'] = 'btn btn-default';
        } elseif (!strstr($addTo['attr']['class'], 'btn-')) {
            $addTo['attr']['class'] .= ' btn btn-default';
        }

        if (self::LOCATION_PAGE_ACTIONS == $this->location) {
            $this->addMobileResponsiveClasses($addTo);
        }
    }

    /**
     * @param array<string,mixed> $button
     */
    private function removeButtonClasses(&$button): void
    {
        if (isset($button['confirm'])) {
            $removeFrom = &$button['confirm'];
        } else {
            $removeFrom = &$button;
        }

        if (!empty($removeFrom['btnClass'])) {
            $removeFrom['attr']['class'] = &$removeFrom['btnClass'];
        } elseif (!isset($removeFrom['attr']['class'])) {
            $removeFrom['attr']['class'] = '';
            $removeFrom['btnClass']      = false;
        }

        $search = [
            'btn-default',
            'btn-primary',
            'btn-success',
            'btn-info',
            'btn-warning',
            'btn-danger',
            'btn',
        ];

        $removeFrom['attr']['class'] = str_replace($search, '', $removeFrom['attr']['class']);
    }
}
