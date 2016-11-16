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
     * @var
     */
    private $preCustomButtons;

    /**
     * @var
     */
    private $postCustomButtons;

    /**
     * @var int
     */
    private $buttonCount = 0;

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
     * @param       $preCustomButtons
     * @param array $postCustomButtons
     *
     * @return $this
     */
    public function setCustomButtons($preCustomButtons, $postCustomButtons = [])
    {
        $this->preCustomButtons  = $preCustomButtons;
        $this->postCustomButtons = $postCustomButtons;

        return $this;
    }

    /**
     * Render custom buttons.
     *
     * @param        $buttonCount
     * @param string $dropdownHtml
     *
     * @return string
     */
    public function renderCustomButtons(&$buttonCount = 0, $dropdownHtml = '')
    {
        return $this->renderPreCustomButtons($buttonCount, $dropdownHtml).$this->renderPostCustomButtons($buttonCount, $dropdownHtml);
    }

    /**
     * @param        $buttonCount
     * @param string $dropdownHtml
     *
     * @return string
     */
    public function renderPreCustomButtons(&$buttonCount, $dropdownHtml = '')
    {
        $preCustomButtonContent = '';
        foreach ($this->preCustomButtons as $c) {
            if ($this->groupType == 'button-dropdown' && $buttonCount === 1) {
                $preCustomButtonContent .= $dropdownHtml;
            }
            $preCustomButtonContent .= $this->buildCustom($c, $buttonCount);
            ++$buttonCount;
        }

        return $preCustomButtonContent;
    }

    /**
     * @param        $buttonCount
     * @param string $dropdownHtml
     *
     * @return string
     */
    public function renderPostCustomButtons(&$buttonCount, $dropdownHtml = '')
    {
        $postCustomButtonContent = '';

        if (!empty($this->postCustomButtons)) {
            foreach ($this->postCustomButtons as $c) {
                if ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount === 1) {
                    $postCustomButtonContent .= $dropdownHtml;
                }
                $postCustomButtonContent .= $this->buildCustom($c, $buttonCount);
                ++$buttonCount;
            }
        }

        return $postCustomButtonContent;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'buttons';
    }

    /**
     * @param Request $request
     * @param null    $item
     *
     * @return $this
     */
    public function fetchCustomButtons(Request $request, $item = null)
    {
        if ($this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS)) {
            $event = $this->dispatcher->dispatch(
                CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS,
                new CustomButtonEvent($this->location, $request, $this->preCustomButtons, $this->postCustomButtons, $item)
            );

            $this->preCustomButtons  = $event->getPrependedButtons();
            $this->postCustomButtons = $event->getAppendedButtons();
        }

        return $this;
    }

    /**
     * @param $buttonCount
     *
     * @return $this
     */
    public function setButtonCount($buttonCount)
    {
        $this->buttonCount = (int) $buttonCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getButtonCount()
    {
        return $this->buttonCount + count($this->postCustomButtons) + count($this->preCustomButtons);
    }

    /**
     * @param $c
     * @param $buttonCount
     *
     * @return string
     */
    protected function buildCustom($c, $buttonCount)
    {
        $buttons = '';

        //Wrap links in a tag
        if ($this->groupType == self::TYPE_DROPDOWN || ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount > 0)) {
            $this->wrapOpeningTag = "<li>\n";
            $this->wrapClosingTag = "</li>\n";
        }

        if (isset($c['confirm'])) {
            if (($this->groupType == self::TYPE_GROUP || ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount === 0))) {
                if (isset($c['confirm']['btnClass']) && !strstr($c['confirm']['btnClass'], 'btn-')) {
                    $c['confirm']['btnClass'] .= ' btn btn-default';
                } elseif (!isset($c['confirm']['btnClass'])) {
                    $c['confirm']['btnClass'] = 'btn btn-default';
                }
            } elseif (in_array($this->groupType, [self::TYPE_BUTTON_DROPDOWN, self::TYPE_DROPDOWN])) {
                if (!isset($c['confirm']['btnClass'])) {
                    $c['confirm']['btnClass'] = '';
                } elseif ($this->groupType == self::TYPE_BUTTON_DROPDOWN) {
                    $search = [
                        'btn-default',
                        'btn-primary',
                        'btn-success',
                        'btn-info',
                        'btn-warning',
                        'btn-danger',
                        'btn',
                    ];
                    $c['confirm']['btnClass'] = str_replace($search, '', $c['attr']['class']);
                }
            }
            $buttons .= $this->wrapOpeningTag.$this->templating->render(
                    'MauticCoreBundle:Helper:confirm.html.php',
                    $c['confirm']
                )."{$this->wrapClosingTag}\n";
        } else {
            $attr = $this->menuLink;

            if (!isset($c['attr'])) {
                $c['attr'] = [];
            }

            if (isset($c['btnClass'])) {
                $c['attr']['class'] = $c['btnClass'];
            }

            if (($this->groupType == self::TYPE_GROUP || ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount === 0))) {
                if (!isset($c['attr']['class'])) {
                    $c['attr']['class'] = 'btn btn-default';
                } elseif (!strstr($c['attr']['class'], 'btn-')) {
                    $c['attr']['class'] .= ' btn btn-default';
                }
            } elseif (($this->groupType == self::TYPE_DROPDOWN || ($this->groupType == self::TYPE_BUTTON_DROPDOWN && $buttonCount > 0))
                && isset($c['attr']['class'])
            ) {
                // Remove btn classes
                $search = [
                    'btn-default',
                    'btn-primary',
                    'btn-success',
                    'btn-info',
                    'btn-warning',
                    'btn-danger',
                    'btn',
                ];
                $c['attr']['class'] = str_replace($search, '', $c['attr']['class']);
            }

            if (!isset($c['attr']['data-toggle'])) {
                $c['attr']['data-toggle'] = 'ajax';
            }

            $tooltipAttr = '';
            if (isset($c['tooltip'])) {
                $tooltipAttr .= ' data-toggle="tooltip"';
                if (is_array($c['tooltip'])) {
                    foreach ($c['tooltip'] as $k => $v) {
                        if ($k == 'title') {
                            $v = $this->translator->trans($v);
                        }
                        $tooltipAttr .= " $k=".'"'.$v.'"';
                    }
                } else {
                    $tooltipAttr .= ' title="'.$this->translator->trans($c['tooltip']).'" data-placement="left"';
                }
            }

            foreach ($c['attr'] as $k => $v) {
                $attr .= " $k=".'"'.$v.'"';
            }

            $buttonContent = (isset($c['iconClass'])) ? '<i class="'.$c['iconClass'].'"></i> ' : '';
            if (!empty($c['btnText'])) {
                $buttonContent .= $this->translator->trans($c['btnText']);
            }
            $buttons .= "{$this->wrapOpeningTag}<a{$attr}><span{$tooltipAttr}>{$buttonContent}</span></a>{$this->wrapClosingTag}\n";
        }

        return $buttons;
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
     * @param     $buttonLocation
     * @param     $groupType
     * @param int $buttonCount
     */
    public function reset($buttonLocation, $groupType = self::TYPE_GROUP, $buttonCount = 0)
    {
        $this->location          = $buttonLocation;
        $this->groupType         = $groupType;
        $this->buttonCount       = $buttonCount;
        $this->preCustomButtons  = [];
        $this->postCustomButtons = [];

        return $this;
    }
}
