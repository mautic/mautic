<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

final readonly class EditorFontsSubscriber
{
    public const string PARAMETER_EDITOR_FONTS = 'editor_fonts';

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    #[AsEventListener(event: CustomAssetsEvent::class)]
    public function addGlobalAssets(CustomAssetsEvent $customAssetsEvent): void
    {
        $this->addEditorFonts($customAssetsEvent);
    }

    private function addEditorFonts(CustomAssetsEvent $customAssetsEvent): void
    {
        $fonts = (array) $this->coreParametersHelper->get(self::PARAMETER_EDITOR_FONTS, []);
        foreach ($fonts as $font) {
            if (empty($font['url'])) {
                continue;
            }

            $customAssetsEvent->addStylesheet($font['url']);
        }
    }
}
