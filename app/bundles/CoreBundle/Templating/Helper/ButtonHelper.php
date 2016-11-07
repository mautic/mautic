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

    private $wrapOpeningTag;

    private $wrapClosingTag;

    private $groupType;

    private $menuLink;

    private $preCustomButtons;

    private $postCustomButtons;

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
     */
    public function setWrappingTags($wrapOpeningTag, $wrapClosingTag)
    {
        $this->wrapOpeningTag = $wrapOpeningTag;
        $this->wrapClosingTag = $wrapClosingTag;
    }

    /**
     * @param $groupType
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;
    }

    /**
     * @param $menuLink
     */
    public function setMenuLink($menuLink)
    {
        $this->menuLink = $menuLink;
    }

    /**
     * @param $preCustomButtons
     * @param $postCustomButtons
     */
    public function setCustomButtons($preCustomButtons, $postCustomButtons)
    {
        $this->preCustomButtons  = $preCustomButtons;
        $this->postCustomButtons = $postCustomButtons;
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
                if ($this->groupType == 'button-dropdown' && $buttonCount === 1) {
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
     * @param         $location
     * @param Request $request
     * @param mixed   $item
     */
    public function fetchCustomButtons($location, Request $request, $item = null)
    {
        if ($this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS)) {
            $event = $this->dispatcher->dispatch(
                CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS,
                new CustomButtonEvent($location, $request, $this->preCustomButtons, $this->postCustomButtons, $item)
            );

            $this->preCustomButtons  = $event->getPrependedButtons();
            $this->postCustomButtons = $event->getAppendedButtons();
        }
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
        if ($this->groupType == 'dropdown' || ($this->groupType == 'button-dropdown' && $buttonCount > 0)) {
            $this->wrapOpeningTag = "<li>\n";
            $this->wrapClosingTag = "</li>\n";
        }

        if (isset($c['confirm'])) {
            if (($this->groupType == 'group' || ($this->groupType == 'button-dropdown' && $buttonCount === 0))) {
                if (isset($c['confirm']['btnClass']) && !strstr($c['confirm']['btnClass'], 'btn-')) {
                    $c['confirm']['btnClass'] .= ' btn btn-default';
                } elseif (!isset($c['confirm']['btnClass'])) {
                    $c['confirm']['btnClass'] = 'btn btn-default';
                }
            } elseif (in_array($this->groupType, ['button-dropdown', 'dropdown'])) {
                if (!isset($c['confirm']['btnClass'])) {
                    $c['confirm']['btnClass'] = '';
                } elseif ($this->groupType == 'button-dropdown') {
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

            if (($this->groupType == 'group' || ($this->groupType == 'button-dropdown' && $buttonCount === 0))) {
                if (!isset($c['attr']['class'])) {
                    $c['attr']['class'] = 'btn btn-default';
                } elseif (!strstr($c['attr']['class'], 'btn-')) {
                    $c['attr']['class'] .= ' btn btn-default';
                }
            } elseif (($this->groupType == 'dropdown' || ($this->groupType == 'button-dropdown' && $buttonCount > 0))
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
}
