<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * PS font support class.
     */
    class WideImage_Font_PS
    {
        public $size;
        public $color;
        public $handle;

        public function __construct($file, $size, $color, $bgcolor = null)
        {
            $this->handle = imagepsloadfont($file);
            $this->size   = $size;
            $this->color  = $color;
            if ($bgcolor === null) {
                $this->bgcolor = $color;
            } else {
                $this->color = $color;
            }
        }

        public function writeText($image, $x, $y, $text, $angle = 0)
        {
            if ($image->isTrueColor()) {
                $image->alphaBlending(true);
            }

            imagepstext($image->getHandle(), $text, $this->handle, $this->size, $this->color, $this->bgcolor, $x, $y, 0, 0, $angle, 4);
        }

        public function __destruct()
        {
            imagepsfreefont($this->handle);
            $this->handle = null;
        }
    }
