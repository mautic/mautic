<?php
	/**
 * @author Gasper Kozak
 * @copyright 2007-2011

    This file is part of WideImage.
		
    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.
		
    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.
		
    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    * @package Internal/Operations
  **/
	
	/**
	 * ApplyMask operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_ApplyMask
	{
		/**
		 * Applies a mask on the copy of source image
		 *
		 * @param WideImage_Image $image
		 * @param WideImage_Image $mask
		 * @param smart_coordinate $left
		 * @param smart_coordinate $top
		 * @return WideImage_Image
		 */
		function execute($image, $mask, $left = 0, $top = 0)
		{
			$left = WideImage_Coordinate::fix($left, $image->getWidth(), $mask->getWidth());
			$top = WideImage_Coordinate::fix($top, $image->getHeight(), $mask->getHeight());
			
			$width = $image->getWidth();
			$mask_width = $mask->getWidth();
			
			$height = $image->getHeight();
			$mask_height = $mask->getHeight();
			
			$result = $image->asTrueColor();
			
			$result->alphaBlending(false);
			$result->saveAlpha(true);
			
			$srcTransparentColor = $result->getTransparentColor();
			if ($srcTransparentColor >= 0)
			{
				# this was here. works without.
				#$trgb = $image->getColorRGB($srcTransparentColor);
				#$trgb['alpha'] = 127;
				#$destTransparentColor = $result->allocateColorAlpha($trgb);
				#$result->setTransparentColor($destTransparentColor);
				$destTransparentColor = $srcTransparentColor;
			}
			else
			{
				$destTransparentColor = $result->allocateColorAlpha(255, 255, 255, 127);
			}
			
			for ($x = 0; $x < $width; $x++)
				for ($y = 0; $y < $height; $y++)
				{
					$mx = $x - $left;
					$my = $y - $top;
					if ($mx >= 0 && $mx < $mask_width && $my >= 0 && $my < $mask_height)
					{
						$srcColor = $image->getColorAt($x, $y);
						if ($srcColor == $srcTransparentColor)
							$destColor = $destTransparentColor;
						else
						{
							$maskRGB = $mask->getRGBAt($mx, $my);
							if ($maskRGB['red'] == 0)
								$destColor = $destTransparentColor;
							elseif ($srcColor >= 0)
							{
								$imageRGB = $image->getRGBAt($x, $y);
								$level = ($maskRGB['red'] / 255) * (1 - $imageRGB['alpha'] / 127);
								$imageRGB['alpha'] = 127 - round($level * 127);
								if ($imageRGB['alpha'] == 127)
									$destColor = $destTransparentColor;
								else
									$destColor = $result->allocateColorAlpha($imageRGB);
							}
							else
								$destColor = $destTransparentColor;
						}
						$result->setColorAt($x, $y, $destColor);
					}
				}
			return $result;
		}
	}
