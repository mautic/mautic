<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\CoreAssetsHelper;
use Symfony\Component\Templating\Helper\SlotsHelper;

class BlockHelper extends SlotsHelper
{

    protected $assetsHelper;

    public function __construct(CoreAssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name    The slot name
     * @param string $content The slot content
     *
     * @api
     */
    public function set($name, $content)
    {
        //prevent the use of internally used keys
        if (in_array($name, array('scripts', 'scriptDeclarations', 'stylesheets',
            'headDeclarations', 'styleDeclarations', 'customDeclaration'))) {
            throw new \InvalidArgumentException($name . ' cannot be manually set.  Please use addScript, addScriptDeclaration, addStylesheet, addStyleDeclaration or addCustomDeclaration');
        }

        parent::set($name, $content);
    }

    /**
     * Appends a slot value if already set
     *
     * @param $name
     * @param $content
     */
    public function append($name, $content)
    {
        if (isset($this->slots[$name]))
        {
            if (is_array($this->slots[$name])) {
                $this->slots[$name][] = $content;
            } else {
                $this->slots[$name] .= " " . $content;
            }
        } else {
            $this->slots[$name] = $content;
        }
    }

    /**
     * Adds a JS script to the template
     *
     * @param string $script
     * @param string $location
     */
    public function addScript($script, $location = 'head')
    {
        $slots =& $this->slots;
        $addScripts = function ($s) use ($location, $slots) {
            if ($location == 'head') {
                //special place for these so that declarations and scripts can be mingled
                $this->slots['headDeclarations'][] = array(
                    'type' => 'script',
                    'src'  => $s
                );
            } else {
                if (!isset($this->slots['scripts'][$location])) {
                    $this->slots['scripts'][$location] = array();
                }

                if (!in_array($s, $this->slots['scripts'][$location])) {
                    $this->slots['scripts'][$location][] = $s;
                }
            }
        };

        if (is_array($script)) {
            foreach ($script as $s) {
                $addScripts($s);
            }
        } else {
            $addScripts($script);
        }
    }

    /**
     * Adds JS script declarations to the template
     *
     * @param string $script
     * @param string $location
     */
    public function addScriptDeclaration($script, $location = 'head')
    {
        if ($location == 'head') {
            //special place for these so that declarations and scripts can be mingled
            $this->slots['headDeclarations'][] = array(
                'type'   => 'declaration',
                'script' => $script
            );
        } else {
            if (!isset($this->slots['scriptDeclarations'][$location])) {
                $this->slots['scriptDeclarations'][$location] = array();
            }

            if (!in_array($script, $this->slots['scriptDeclarations'][$location])) {
                $this->slots['scriptDeclarations'][$location][] = $script;
            }
        }
    }

    /**
     * Adds a stylesheet to be loaded in the template header
     *
     * @param string $stylesheet
     */
    public function addStylesheet($stylesheet)
    {
        $slots =& $this->slots;
        $addSheet = function ($s) use ($slots) {
            if (!isset($this->slots['stylesheets'])) {
                $this->slots['stylesheets'] = array();
            }

            if (!in_array($s, $this->slots['stylesheets'])) {
                $this->slots['stylesheets'][] = $s;
            }
        };

        if (is_array($stylesheet)) {
            foreach ($stylesheet as $s) {
                $addSheet($s);
            }
        } else {
            $addSheet($stylesheet);
        }
    }

    /**
     * Add style tag to the header
     *
     * @param $styles
     */
    public function addStyleDeclaration($styles)
    {
        if (!isset($this->slots['styleDeclarations'])) {
            $this->slots['styleDeclarations'] = array();
        }

        if (!in_array($styles, $this->slots['styleDeclarations'])) {
            $this->slots['styleDeclarations'][] = $styles;
        }
    }

    /**
     * Adds a custom declaration to <head />
     *
     * @param $declaration
     */
    public function addCustomDeclaration($declaration)
    {
        $this->slots['headDeclaration'][] = array(
            'type'        => 'custom',
            'declaration' => $declaration
        );
    }

    /**
     * Outputs the stylesheets and style declarations
     */
    public function outputStyles()
    {
        if (isset($this->slots['stylesheets'])) {
            foreach ($this->slots['stylesheets'] as $s) {
                echo '<link rel="stylesheet" href="' . $this->assetsHelper->getUrl($s) . '" />' . "\n";
            }
        }

        if (isset($this->slots['styleDeclarations'])) {
            echo "<style>\n";
            foreach ($this->slots['styleDeclarations'] as $d) {
                echo "$d\n";
            }
            echo "</style>\n";
        }
    }

    /**
     * Outputs the script files and declarations
     *
     * @param string $location
     */
    public function outputScripts($location)
    {
        if (isset($this->slots['scripts'][$location])) {
            foreach ($this->slots['scripts'][$location] as $s) {
                echo '<script src="'.$this->assetsHelper->getUrl($s).'"></script>'."\n";
            }
        }

        if (isset($this->slots['scriptDeclarations'][$location])) {
            echo "<script>\n";
            foreach ($this->slots['scriptDeclarations'][$location] as $d) {
                echo "$d\n";
            }
            echo "</script>\n";
        }
    }

    public function outputHeadDeclarations()
    {
        $this->outputStyles();

        if (isset($this->slots['headDeclarations'])) {
            foreach ($this->slots['headDeclarations'] as $h) {
                if ($h['type'] == 'script') {
                    echo '<script src="'.$this->assetsHelper->getUrl($h['src']).'"></script>'."\n";
                } elseif ($h['type'] == 'declaration') {
                    echo "<script>\n{$h['script']}\n</script>\n";
                } else {
                    echo $h['declaration'] . "\n";
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'blocks';
    }
}