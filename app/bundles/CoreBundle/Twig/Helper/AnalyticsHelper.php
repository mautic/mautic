<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class AnalyticsHelper
{
    private string $code;
    private string $bodyCode;

    public function __construct(CoreParametersHelper $parametersHelper)
    {
        $this->code     = htmlspecialchars_decode((string) $parametersHelper->get('google_analytics'));
        $this->bodyCode = htmlspecialchars_decode((string) $parametersHelper->get('google_analytics_body'));
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getBodyCode(): string
    {
        return $this->bodyCode;
    }

    /**
     * @param string $content
     */
    public function addCode($content): string
    {
        // Add analytics
        $analytics     = $this->getCode();
        $bodyAnalytics = $this->getBodyCode();

        // Check for html doc
        if (!str_contains($content, '<html')) {
            $content = "<html>\n<head>{$analytics}</head>\n<body>{$content}{$bodyAnalytics}</body>\n</html>";
        } elseif (!str_contains($content, '<head>')) {
            $content = str_replace('<html>', "<html>\n<head>\n{$analytics}\n</head>", $content);
            if (!empty($bodyAnalytics)) {
                $content = str_replace('</body>', $bodyAnalytics."\n</body>", $content);
            }
        } else {
            if (!empty($analytics)) {
                $content = str_replace('</head>', $analytics."\n</head>", $content);
            }
            if (!empty($bodyAnalytics)) {
                $content = str_replace('</body>', $bodyAnalytics."\n</body>", $content);
            }
        }

        return $content;
    }

    public function getName(): string
    {
        return 'analytics';
    }
}
