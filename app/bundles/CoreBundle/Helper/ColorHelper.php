<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Helper class for operations with colors.
 */
class ColorHelper
{
    /**
     * @var int
     */
    protected $red = 0;

    /**
     * @var int
     */
    protected $green = 0;

    /**
     * @var int
     */
    protected $blue = 0;

    /**
     * Constructor.
     *
     * @param  string in format #xxxxxx or #xxx
     */
    public function __construct($hex = null)
    {
        if ($hex) {
            $this->setHex($hex);
        }
    }

    /**
     * Sets random values to RGB properties. It will avoid too black or too wight colors.
     *
     * @return ColorHelper
     */
    public function buildRandomColor()
    {
        $this->red   = rand(20, 236);
        $this->green = rand(20, 236);
        $this->blue  = rand(20, 236);

        return $this;
    }

    /**
     * Populate color from hexadecimal code.
     *
     * @param  string in format #xxxxxx or #xxx
     *
     * @return ColorHelper
     */
    public function setHex($hex)
    {
        if (strlen($hex) === 4) {
            $format          = '#%1s%1s%1s';
            list($r, $g, $b) = sscanf($hex, $format);
            $this->red       = hexdec("$r$r");
            $this->green     = hexdec("$g$g");
            $this->blue      = hexdec("$b$b");
        } else {
            $format                                     = '#%2x%2x%2x';
            list($this->red, $this->green, $this->blue) = sscanf($hex, $format);
        }

        return $this;
    }

    /**
     * Returns array of [R, G, B] of current state.
     *
     * @return array
     */
    public function getColorArray()
    {
        return [$this->red, $this->green, $this->blue];
    }

    /**
     * Returns array of [R, G, B] of current state.
     *
     * @return string
     */
    public function toRgb()
    {
        return sprintf('rgb(%d,%d,%d)', $this->red, $this->green, $this->blue);
    }

    /**
     * Returns array of [R, G, B] of current state with alpha.
     *
     * @param  float (0 - 1)
     *
     * @return string
     */
    public function toRgba($alpha = 1)
    {
        return sprintf('rgba(%d,%d,%d,%g)', $this->red, $this->green, $this->blue, (float) $alpha);
    }

    /**
     * Returns current color to hexadecimal hash.
     *
     * @return string
     */
    public function toHex()
    {
        $hex = '#';
        $hex .= str_pad(dechex($this->red), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($this->green), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($this->blue), 2, '0', STR_PAD_LEFT);

        return $hex;
    }
}
