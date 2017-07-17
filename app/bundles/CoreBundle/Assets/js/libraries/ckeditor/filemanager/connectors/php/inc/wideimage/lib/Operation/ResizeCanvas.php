<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * ResizeCanvas operation class.
     */
    class WideImage_Operation_ResizeCanvas
    {
        /**
         * Returns an image with a resized canvas.
         *
         * The image is filled with $color. Use $scale to determine, when to resize.
         *
         * @param WideImage_Image  $img
         * @param smart_coordinate $width
         * @param smart_coordinate $height
         * @param smart_coordinate $left
         * @param smart_coordinate $top
         * @param int              $color
         * @param string           $scale  'up', 'down', 'any'
         * @param bool             $merge
         *
         * @return WideImage_Image
         */
        public function execute($img, $width, $height, $left, $top, $color, $scale, $merge)
        {
            $new_width  = WideImage_Coordinate::fix($width, $img->getWidth());
            $new_height = WideImage_Coordinate::fix($height, $img->getHeight());

            if ($scale == 'down') {
                $new_width  = min($new_width, $img->getWidth());
                $new_height = min($new_height, $img->getHeight());
            } elseif ($scale == 'up') {
                $new_width  = max($new_width, $img->getWidth());
                $new_height = max($new_height, $img->getHeight());
            }

            $new = WideImage::createTrueColorImage($new_width, $new_height);
            if ($img->isTrueColor()) {
                if ($color === null) {
                    $color = $new->allocateColorAlpha(0, 0, 0, 127);
                }
            } else {
                imagepalettecopy($new->getHandle(), $img->getHandle());

                if ($img->isTransparent()) {
                    $new->copyTransparencyFrom($img);
                    $tc_rgb  = $img->getTransparentColorRGB();
                    $t_color = $new->allocateColorAlpha($tc_rgb);
                }

                if ($color === null) {
                    if ($img->isTransparent()) {
                        $color = $t_color;
                    } else {
                        $color = $new->allocateColorAlpha(255, 0, 127, 127);
                    }

                    imagecolortransparent($new->getHandle(), $color);
                }
            }
            $new->fill(0, 0, $color);

            $x = WideImage_Coordinate::fix($left, $new->getWidth(), $img->getWidth());
            $y = WideImage_Coordinate::fix($top, $new->getHeight(), $img->getHeight());

            // blending for truecolor images
            if ($img->isTrueColor()) {
                $new->alphaBlending($merge);
            }

            // not-blending for palette images
            if (!$merge && !$img->isTrueColor() && isset($t_color)) {
                $new->getCanvas()->filledRectangle($x, $y, $x + $img->getWidth(), $y + $img->getHeight(), $t_color);
            }

            $img->copyTo($new, $x, $y);

            return $new;
        }
    }
