<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * TTF font support class.
     */
    class WideImage_Font_TTF
    {
        public $face;
        public $size;
        public $color;

        public function __construct($face, $size, $color)
        {
            $this->face  = $face;
            $this->size  = $size;
            $this->color = $color;
        }

        /**
         * Writes text onto an image.
         *
         * @param WideImage_Image $image
         * @param mixed           $x     smart coordinate
         * @param mixed           $y     smart coordinate
         * @param string          $text
         * @param int             $angle Angle in degrees clockwise
         */
        public function writeText($image, $x, $y, $text, $angle = 0)
        {
            if ($image->isTrueColor()) {
                $image->alphaBlending(true);
            }

            $box  = imageftbbox($this->size, $angle, $this->face, $text);
            $obox = [
                'left'   => min($box[0], $box[2], $box[4], $box[6]),
                'top'    => min($box[1], $box[3], $box[5], $box[7]),
                'right'  => max($box[0], $box[2], $box[4], $box[6]) - 1,
                'bottom' => max($box[1], $box[3], $box[5], $box[7]) - 1,
            ];
            $obox['width']  = abs($obox['left']) + abs($obox['right']);
            $obox['height'] = abs($obox['top']) + abs($obox['bottom']);

            $x = WideImage_Coordinate::fix($x, $image->getWidth(), $obox['width']);
            $y = WideImage_Coordinate::fix($y, $image->getHeight(), $obox['height']);

            $fixed_x = $x - $obox['left'];
            $fixed_y = $y - $obox['top'];

            imagettftext($image->getHandle(), $this->size, $angle, $fixed_x, $fixed_y, $this->color, $this->face, $text);
        }
    }
