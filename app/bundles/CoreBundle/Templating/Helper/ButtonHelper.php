<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ButtonHelper.
 */
class ButtonHelper extends Helper
{
    /**
     * List dropdown actions.
     */
    const LOCATION_LIST_ACTIONS = 'list_actions';

    /**
     * Toolbar actions.
     */
    const LOCATION_TOOLBAR_ACTIONS = 'toolbar_actions';

    /**
     * Page actions.
     */
    const LOCATION_PAGE_ACTIONS = 'page_actions';

    /**
     * Navbar actions.
     */
    const LOCATION_NAVBAR = 'navbar_actions';

    /**
     * Bulk actions.
     */
    const LOCATION_BULK_ACTIONS = 'bulk_actions';

    /**
     * Buttons are displayed in group and/or dropdown depending on button count.
     */
    const TYPE_BUTTON_DROPDOWN = 'button-dropdown';

    /**
     * Buttons are displayed in dropdown depending on button count.
     */
    const TYPE_DROPDOWN = 'dropdown';

    /**
     * Buttons are grouped together.
     */
    const TYPE_GROUP = 'group';

    /**
     * Location of the buttons.
     *
     * @var
     */
    private $location;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    private $templating;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var
     */
    private $wrapOpeningTag;

    /**
     * @var
     */
    private $wrapClosingTag;

    /**
     * @var
     */
    private $groupType = self::TYPE_GROUP;

    /**
     * @var
     */
    private $menuLink;

    /**
     * @var array
     */
    private $buttons = [];

    /**
     * @var int
     */
    private $buttonCount = 0;

    /**
     * @var bool
     */
    private $buttonsFetched = false;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var
     */
    private $item;

    /**
     * @var
     */
    private $listMarker = 3;

    /**
     * ButtonHelper constructor.
     *
     * @param EngineInterface          $templating
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EngineInterface $templating, TranslatorInterface $translator, EventDispatcherInterface $dispatcher)
    {
        $this->templating = $templating;
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array $buttons
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
     * @param array $button
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
     * @param $wrapOpeningTag
     * @param $wrapClosingTag
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
     * @param $groupType
     *
     * @return $this
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;

        return $this;
    }

    /**
     * @param $menuLink
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
     *
     * @return string
     */
    public function renderButtons($dropdownHtml = '', $closingDropdownHtml = '')
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
                if ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $nextButton === $this->listMarker && $buttonCount !== $this->buttonCount) {
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
     * @param         $buttonCount
     * @param Request $request
     * @param string  $groupType
     * @param null    $item
     *
     * @return $this
     */
    public function reset(Request $request, $buttonCount, $groupType = self::TYPE_GROUP, $item = null)
    {
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

    /**
     * @return string
     */
    public function getName()
    {
        return 'buttons';
    }

    /**
     * @param     $button
     * @param int $buttonCount
     *
     * @return string
     */
    protected function buildButton($button, $buttonCount = 0)
    {
        $buttons = '';

        //Wrap links in a tag
        if ($this->groupType == self::TYPE_DROPDOWN || ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount >= $this->listMarker)) {
            $this->wrapOpeningTag = "<li>\n";
            $this->wrapClosingTag = "</li>\n";
        }

        if (!isset($button['attr'])) {
            $button['attr'] = [];
        }

        if ($this->groupType == self::TYPE_GROUP || ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount < $this->listMarker)) {
            $this->addButtonClasses($button);
        } elseif (in_array($this->groupType, [self::TYPE_BUTTON_DROPDOWN, self::TYPE_DROPDOWN])) {
            $this->removeButtonClasses($button);
        }

        if (isset($button['confirm'])) {
            $button['confirm']['btnTextAttr'] = $this->generateTextAttributes($button);
            $buttons .= $this->wrapOpeningTag.$this->templating->render('MauticCoreBundle:Helper:confirm.html.php', $button['confirm']).
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
    protected function fetchCustomButtons()
    {
        if (!$this->buttonsFetched && $this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS)) {
            $event = $this->dispatcher->dispatch(
                CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS,
                new CustomButtonEvent($this->location, $this->request, $this->buttons, $this->item)
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
    protected function orderButtons()
    {
        foreach ($this->buttons as $key => $button) {
            $this->validatePriority($this->buttons[$key]);
        }

        uasort(
            $this->buttons,
            function ($a, $b) {
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
     * @param $button
     */
    protected function validatePriority(&$button)
    {
        if (!empty($button['primary'])) {
            if (!isset($button['priority']) || (isset($button['priority']) && $button['priority'] < 200)) {
                $button['priority'] = 215;
            }
        } elseif (!isset($button['priority'])) {
            $button['priority'] = 0;
        }
    }

    /**
     * @param array $button
     *
     * @return string
     */
    protected function generateTextAttributes(&$button)
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
     * @param $button
     *
     * @return string
     */
    protected function generateTooltipAttributes($button)
    {
        $tooltip = '';
        if (isset($button['tooltip'])) {
            $tooltip .= ' data-toggle="tooltip"';
            if (is_array($button['tooltip'])) {
                foreach ($button['tooltip'] as $k => $v) {
                    if ($k == 'title') {
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
     * @param $button
     */
    protected function addMobileResponsiveClasses(&$button)
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
     * @param $button
     */
    protected function addButtonClasses(&$button)
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
     * @param $button
     */
    protected function removeButtonClasses(&$button)
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

    /**
     * @param       $preCustomButtons
     * @param array $postCustomButtons
     *
     * @deprecated 2.3 to be removed 3.0; use renderButtons() instead
     *
     * @return $this
     */
    public function setCustomButtons($preCustomButtons, $postCustomButtons = [])
    {
        // Give preCustomButtons high priority
        foreach ($preCustomButtons as $key => $button) {
            $preCustomButtons[$key]['priority'] = 199;
            $this->buttons[]                    = $preCustomButtons[$key];
            ++$this->buttonCount;
        }

        foreach ($postCustomButtons as $key => $button) {
            $postCustomButtons[$key]['priority'] = 0;
            $this->buttons[]                     = $postCustomButtons[$key];
            ++$this->buttonCount;
        }

        return $this;
    }

    /**
     * @param        $buttonCount
     * @param string $dropdownHtml
     *
     * @deprecated 2.3 to be removed 3.0; use renderButtons() instead
     *
     * @return string
     */
    public function renderPreCustomButtons(&$buttonCount, $dropdownHtml = '')
    {
        return $this->renderButtons($dropdownHtml);
    }

    /**
     * @param        $buttonCount
     * @param string $dropdownHtml
     *
     * @deprecated 2.3; to be removed 3.0
     *
     * @return string
     */
    public function renderPostCustomButtons(&$buttonCount, $dropdownHtml = '')
    {
        return '';
    }
}
