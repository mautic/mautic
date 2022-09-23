<?php

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Templating\Helper\Helper;

class AnalyticsHelper extends Helper
{
    /**
     * @var string
     */
    private $code;

    /**
     * AnalyticsHelper constructor.
     */
    public function __construct(CoreParametersHelper $parametersHelper)
    {
        $this->code = htmlspecialchars_decode($parametersHelper->get('google_analytics', ''));
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $content
     */
    public function addCode($content)
    {
        // Add analytics
        $analytics = $this->getCode();

        // Check for html doc
        if (false === strpos($content, '<html')) {
            $content = "<html>\n<head>{$analytics}</head>\n<body>{$content}</body>\n</html>";
        } elseif (false === strpos($content, '<head>')) {
            $content = str_replace('<html>', "<html>\n<head>\n{$analytics}\n</head>", $content);
        } elseif (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        return $content;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'analytics';
    }
}
