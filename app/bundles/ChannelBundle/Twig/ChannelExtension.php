<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Twig;

use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Templating\Helper\DncReasonHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ChannelExtension extends AbstractExtension
{
    private DncReasonHelper $dncReasonHelper;
    private TranslatorInterface $translator;

    public function __construct(DncReasonHelper $dncReasonHelper, TranslatorInterface $translator)
    {
        $this->dncReasonHelper = $dncReasonHelper;
        $this->translator      = $translator;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('channelOutput', [$this, 'channelOutput']),
        ];
    }

    /**
     * @param array<string, mixed> $log
     */
    public function channelOutput(string $channel, array $log): string
    {
        try {
            if (!empty($log['metadata'][$channel]['dnc'])) {
                return $this->dncReasonHelper->toText((int) $log['metadata'][$channel]['dnc']);
            }
        } catch (UnknownDncReasonException $e) {
            return $e->getMessage();
        }

        return $this->translator->trans('mautic.core.unknown');
    }
}
