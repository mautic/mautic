<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class AnalyticsHelper
{
    private string $code;

    public function __construct(CoreParametersHelper $parametersHelper)
    {
        $this->code = htmlspecialchars_decode((string) $parametersHelper->get('google_analytics'));
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $content
     */
    public function addCode($content): string
    {
        // Add analytics
        $analytics = $this->getCode();

        // Check for html doc
        if (!str_contains($content, '<html')) {
            $content = "<html>\n<head>{$analytics}</head>\n<body>{$content}</body>\n</html>";
        } elseif (!str_contains($content, '<head>')) {
            $content = str_replace('<html>', "<html>\n<head>\n{$analytics}\n</head>", $content);
        } elseif (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        return $content;
    }

    public function getName(): string
    {
        return 'analytics';
    }
}
