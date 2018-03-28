<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011
     **/

    /**
     * Unsharp filter.
     *
     * This filter was taken from http://vikjavev.no/computing/ump.php,
     * the original author Torstein Hønsi. Adapted to fit better within
     * the Wideimage package.
     */
    class WideImage_Operation_Unsharp
    {
        /**
         * Returns sharpened image.
         *
         * @param WideImage_Image $image
         * @param float           $amount
         * @param int             $radius
         * @param float           $threshold
         *
         * @return WideImage_Image
         */
        public function execute($image, $amount, $radius, $threshold)
        {
            // Attempt to calibrate the parameters to Photoshop:
            if ($amount > 500) {
                $amount = 500;
            }
            $amount = $amount * 0.016;
            if ($radius > 50) {
                $radius = 50;
            }
            $radius = $radius * 2;
            if ($threshold > 255) {
                $threshold = 255;
            }

            $radius = abs(round($radius));     // Only integers make sense.
            if ($radius == 0) {
                return $image;
            }

            // Gaussian blur matrix

            $matrix = [
                [1, 2, 1],
                [2, 4, 2],
                [1, 2, 1],
            ];

            $blurred = $image->applyConvolution($matrix, 16, 0);

            if ($threshold > 0) {
                // Calculate the difference between the blurred pixels and the original
                // and set the pixels
                for ($x = 0; $x < $image->getWidth(); ++$x) {
                    for ($y = 0; $y < $image->getHeight(); ++$y) {
                        $rgbOrig = $image->getRGBAt($x, $y);
                        $rOrig   = $rgbOrig['red'];
                        $gOrig   = $rgbOrig['green'];
                        $bOrig   = $rgbOrig['blue'];

                        $rgbBlur = $blurred->getRGBAt($x, $y);
                        $rBlur   = $rgbBlur['red'];
                        $gBlur   = $rgbBlur['green'];
                        $bBlur   = $rgbBlur['blue'];

                        // When the masked pixels differ less from the original
                        // than the threshold specifies, they are set to their original value.
                        $rNew = (abs($rOrig - $rBlur) >= $threshold)
                            ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
                            : $rOrig;
                        $gNew = (abs($gOrig - $gBlur) >= $threshold)
                            ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
                            : $gOrig;
                        $bNew = (abs($bOrig - $bBlur) >= $threshold)
                            ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
                            : $bOrig;
                        $rgbNew = ['red' => $rNew, 'green' => $gNew, 'blue' => $bNew, 'alpha' => 0];

                        if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                            $image->setRGBAt($x, $y, $rgbNew);
                        }
                    }
                }
            } else {
                $w = $image->getWidth();
                $h = $image->getHeight();
                for ($x = 0; $x < $w; ++$x) {
                    for ($y = 0; $y < $h; ++$y) {
                        $rgbOrig = $image->getRGBAt($x, $y);
                        $rOrig   = $rgbOrig['red'];
                        $gOrig   = $rgbOrig['green'];
                        $bOrig   = $rgbOrig['blue'];

                        $rgbBlur = $blurred->getRGBAt($x, $y);
                        $rBlur   = $rgbBlur['red'];
                        $gBlur   = $rgbBlur['green'];
                        $bBlur   = $rgbBlur['blue'];

                        $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                        if ($rNew > 255) {
                            $rNew = 255;
                        } elseif ($rNew < 0) {
                            $rNew = 0;
                        }
                        $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
                        if ($gNew > 255) {
                            $gNew = 255;
                        } elseif ($gNew < 0) {
                            $gNew = 0;
                        }
                        $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                        if ($bNew > 255) {
                            $bNew = 255;
                        } elseif ($bNew < 0) {
                            $bNew = 0;
                        }
                        $rgbNew = ['red' => $rNew, 'green' => $gNew, 'blue' => $bNew, 'alpha' => 0];

                        $image->setRGBAt($x, $y, $rgbNew);
                    }
                }
            }

            return $image;
        }
    }
