<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * CopyChannelsTrueColor operation class.
     *
     * Used to perform CopyChannels operation on truecolor images
     */
    class WideImage_Operation_CopyChannelsTrueColor
    {
        /**
         * Returns an image with only specified channels copied.
         *
         * @param WideImage_Image $img
         * @param array           $channels
         *
         * @return WideImage_Image
         */
        public function execute($img, $channels)
        {
            $blank = ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0];

            $width  = $img->getWidth();
            $height = $img->getHeight();
            $copy   = WideImage_TrueColorImage::create($width, $height);

            if (count($channels) > 0) {
                for ($x = 0; $x < $width; ++$x) {
                    for ($y = 0; $y < $height; ++$y) {
                        $RGBA    = $img->getRGBAt($x, $y);
                        $newRGBA = $blank;
                        foreach ($channels as $channel) {
                            $newRGBA[$channel] = $RGBA[$channel];
                        }

                        $color = $copy->getExactColorAlpha($newRGBA);
                        if ($color == -1) {
                            $color = $copy->allocateColorAlpha($newRGBA);
                        }

                        $copy->setColorAt($x, $y, $color);
                    }
                }
            }

            return $copy;
        }
    }
