<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Merge operation class.
     */
    class WideImage_Operation_Merge
    {
        /**
         * Returns a merged image.
         *
         * @param WideImage_Image  $base
         * @param WideImage_Image  $overlay
         * @param smart_coordinate $left
         * @param smart_coordinate $top
         * @param numeric          $pct
         *
         * @return WideImage_Image
         */
        public function execute($base, $overlay, $left, $top, $pct)
        {
            $x = WideImage_Coordinate::fix($left, $base->getWidth(), $overlay->getWidth());
            $y = WideImage_Coordinate::fix($top, $base->getHeight(), $overlay->getHeight());

            $result = $base->asTrueColor();
            $result->alphaBlending(true);
            $result->saveAlpha(true);

            if ($pct <= 0) {
                return $result;
            }

            if ($pct < 100) {
                if (!imagecopymerge(
                    $result->getHandle(),
                    $overlay->getHandle(),
                    $x, $y, 0, 0,
                    $overlay->getWidth(),
                    $overlay->getHeight(),
                    $pct)) {
                    throw new WideImage_GDFunctionResultException('imagecopymerge() returned false');
                }
            } else {
                if (!imagecopy(
                    $result->getHandle(),
                    $overlay->getHandle(),
                    $x, $y, 0, 0,
                    $overlay->getWidth(),
                    $overlay->getHeight())) {
                    throw new WideImage_GDFunctionResultException('imagecopy() returned false');
                }
            }

            return $result;
        }
    }
