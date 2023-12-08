<?php

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
     * @param string $hex in format #xxxxxx or #xxx
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
        $this->red   = random_int(20, 236);
        $this->green = random_int(20, 236);
        $this->blue  = random_int(20, 236);

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
        if (4 === strlen($hex)) {
            $format          = '#%1s%1s%1s';
            [$r, $g, $b]     = sscanf($hex, $format);
            $this->red       = hexdec("$r$r");
            $this->green     = hexdec("$g$g");
            $this->blue      = hexdec("$b$b");
        } else {
            $format                                     = '#%2x%2x%2x';
            [$this->red, $this->green, $this->blue]     = sscanf($hex, $format);
        }

        return $this;
    }

    /**
     * Returns array of [R, G, B] of current state.
     */
    public function getColorArray(): array
    {
        return [$this->red, $this->green, $this->blue];
    }

    /**
     * Returns array of [R, G, B] of current state.
     */
    public function toRgb(): string
    {
        return sprintf('rgb(%d,%d,%d)', $this->red, $this->green, $this->blue);
    }

    /**
     * Returns array of [R, G, B] of current state with alpha.
     *
     * @param  float (0 - 1)
     */
    public function toRgba($alpha = 1): string
    {
        return sprintf('rgba(%d,%d,%d,%g)', $this->red, $this->green, $this->blue, (float) $alpha);
    }

    /**
     * Returns current color to hexadecimal hash.
     */
    public function toHex(): string
    {
        $hex = '#';
        $hex .= str_pad(dechex($this->red), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($this->green), 2, '0', STR_PAD_LEFT);

        return $hex.str_pad(dechex($this->blue), 2, '0', STR_PAD_LEFT);
    }

    public function getRed(): int
    {
        return $this->red;
    }

    public function getGreen(): int
    {
        return $this->green;
    }

    public function getBlue(): int
    {
        return $this->blue;
    }
}
