<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/
    class WideImage_NoFontException extends WideImage_Exception
    {
    }

    class WideImage_InvalidFontFileException extends WideImage_Exception
    {
    }

    class WideImage_InvalidCanvasMethodException extends WideImage_Exception
    {
    }

    class WideImage_Canvas
    {
        protected $handle = 0;
        protected $image  = null;
        protected $font   = null;

        /**
         * Creates a canvas object that writes to the image passed as a parameter.
         *
         * Shouldn't be used directly, use WideImage_Image::getCanvas() instead.
         *
         * @param WideImage_Image $img Image object
         */
        public function __construct($img)
        {
            $this->handle = $img->getHandle();
            $this->image  = $img;
        }

        /**
         * Sets the active font. Can be an instance of
         * WideImage_Font_TTF, WideImage_Font_PS, or WideImage_Font_GDF.
         *
         *
         *
         *
         * @param object $font Font object to set for writeText()
         */
        public function setFont($font)
        {
            $this->font = $font;
        }

        /**
         * Creates and sets the current font.
         *
         * The supported font types are: TTF/OTF, PS, and GDF.
         * Font type is detected from the extension. If the $file parameter doesn't have an extension, TTF font is presumed.
         *
         * Note: not all parameters are supported by all fonts.
         *
         * @param string $file    Font file name (string)
         * @param int    $size    Font size (supported for TTF/OTF and PS fonts, ignored for GDF)
         * @param int    $color   Text color
         * @param int    $bgcolor Background color (supported only for PS font, ignored for TTF and PS)
         *
         * @return mixed One of the WideImage_Font_* objects
         */
        public function useFont($file, $size = 12, $color = 0, $bgcolor = null)
        {
            $p = strrpos($file, '.');
            if ($p === false || $p < strlen($file) - 4) {
                $ext = 'ttf';
            } else {
                $ext = strtolower(substr($file, $p + 1));
            }

            if ($ext == 'ttf' || $ext == 'otf') {
                $font = new WideImage_Font_TTF($file, $size, $color);
            } elseif ($ext == 'ps') {
                $font = new WideImage_Font_PS($file, $size, $color, $bgcolor);
            } elseif ($ext == 'gdf') {
                $font = new WideImage_Font_GDF($file, $color);
            } else {
                throw new WideImage_InvalidFontFileException("'$file' appears to be an invalid font file.");
            }

            $this->setFont($font);

            return $font;
        }

        /**
         * Write text on the image at specified position.
         *
         * You must set a font with a call to WideImage_Canvas::setFont() prior to writing text to the image.
         *
         * Smart coordinates are supported for $x and $y arguments, but currently only for TTF/OTF fonts.
         *
         * Example:
         * <code>
         * $img = WideImage::load('pic.jpg');
         * $canvas = $img->getCanvas();
         * $canvas->useFont('Verdana.ttf', 16, $img->allocateColor(255, 0, 0));
         * $canvas->writeText('right', 'bottom', 'www.website.com');
         * </code>
         *
         * @param int    $x     Left
         * @param int    $y     Top
         * @param string $text  Text to write
         * @param int    $angle The angle, defaults to 0
         */
        public function writeText($x, $y, $text, $angle = 0)
        {
            if ($this->font === null) {
                throw new WideImage_NoFontException("Can't write text without a font.");
            }

            $angle = -floatval($angle);
            if ($angle < 0) {
                $angle = 360 + $angle;
            }
            $angle = $angle % 360;

            $this->font->writeText($this->image, $x, $y, $text, $angle);
        }

        /**
         * A magic method that allows you to call any PHP function that starts with "image".
         *
         * This is a shortcut to call custom functions on the image handle.
         *
         * Example:
         * <code>
         * $img = WideImage::load('pic.jpg');
         * $canvas = $img->getCanvas();
         * $canvas->filledRect(10, 10, 20, 30, $img->allocateColor(0, 0, 0));
         * $canvas->line(60, 80, 30, 100, $img->allocateColor(255, 0, 0));
         * </code>
         */
        public function __call($method, $params)
        {
            if (function_exists('image'.$method)) {
                array_unshift($params, $this->handle);
                call_user_func_array('image'.$method, $params);
            } else {
                throw new WideImage_InvalidCanvasMethodException("Function doesn't exist: image{$method}.");
            }
        }
    }
