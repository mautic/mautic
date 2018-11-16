<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param CoreParametersHelper $parametersHelper
     */
    public function __construct(CoreParametersHelper $parametersHelper)
    {
        $this->code = htmlspecialchars_decode($parametersHelper->getParameter('google_analytics', ''));
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
        if (strpos($content, '<html') === false) {
            $content = "<html>\n<head>{$analytics}</head>\n<body>{$content}</body>\n</html>";
        } elseif (strpos($content, '<head>') === false) {
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
