<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Templating\Helper\Helper;

class GTMHelper extends Helper
{
    /**
     * @var string
     */
    private $code;

    /**
     * GTMHelper constructor.
     */
    public function __construct(CoreParametersHelper $parametersHelper)
    {
        $this->code        = $parametersHelper->get('google_tag_manager_id', '');
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
        // Add Google Tag Manager
        $gtmHead = $this->getHeadGTMCode();
        $gtmBody = $this->getBodyGTMCode();
        // Check for html doc
        if (false === strpos($content, '<html')) {
            $content = "<html>\n<head>{$gtmHead}</head>\n<body>{$gtmBody}\n{$content}</body>\n</html>";
        } elseif (false === strpos($content, '<head>')) {
            $content = str_replace('<html>', "<html>\n<head>\n{$gtmHead}\n</head>", $content);
        } elseif (!empty($gtm)) {
            $content = str_replace('</head>', $gtm."\n</head>", $content);
        }

        return $content;
    }

    /**
     * @return string
     */
    public function getHeadGTMCode()
    {
        $id = $this->code;
        $js = "
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{$id}');</script>";

        return $id ? $js : '';
    }

    /**
     * @return string
     */
    public function getBodyGTMCode()
    {
        $id = $this->code;
        $js = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$this->code.'"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

        return $id ? $js : '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'google_tag_manager';
    }
}
