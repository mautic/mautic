<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * AsNegative operation class.
     */
    class WideImage_Operation_AsNegative
    {
        /**
         * Returns a greyscale copy of an image.
         *
         * @param WideImage_Image $image
         *
         * @return WideImage_Image
         */
        public function execute($image)
        {
            $palette     = !$image->isTrueColor();
            $transparent = $image->isTransparent();

            if ($palette && $transparent) {
                $tcrgb = $image->getTransparentColorRGB();
            }

            $new = $image->asTrueColor();
            if (!imagefilter($new->getHandle(), IMG_FILTER_NEGATE)) {
                throw new WideImage_GDFunctionResultException('imagefilter() returned false');
            }

            if ($palette) {
                $new = $new->asPalette();
                if ($transparent) {
                    $irgb = ['red' => 255 - $tcrgb['red'], 'green' => 255 - $tcrgb['green'], 'blue' => 255 - $tcrgb['blue'], 'alpha' => 127];
                    // needs imagecolorexactalpha instead of imagecolorexact, otherwise doesn't work on some transparent GIF images
                    $new_tci = imagecolorexactalpha($new->getHandle(), $irgb['red'], $irgb['green'], $irgb['blue'], 127);
                    $new->setTransparentColor($new_tci);
                }
            }

            return $new;
        }
    }
