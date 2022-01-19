<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EditorFontsSubscriber implements EventSubscriberInterface
{
    public const PARAMETER_EDITOR_FONTS = 'editor_fonts';

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['addGlobalAssets', 0],
        ];
    }

    public function addGlobalAssets(CustomAssetsEvent $customAssetsEvent): void
    {
        $this->addEditorFonts($customAssetsEvent);
    }

    private function addEditorFonts(CustomAssetsEvent $customAssetsEvent): void
    {
        $fonts = (array) $this->coreParametersHelper->get(static::PARAMETER_EDITOR_FONTS, []);
        foreach ($fonts as $font) {
            if (empty($font['url'])) {
                continue;
            }

            $customAssetsEvent->addStylesheet($font['url']);
        }
    }
}
