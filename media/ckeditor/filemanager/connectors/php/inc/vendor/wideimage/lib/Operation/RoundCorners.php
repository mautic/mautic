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
	class WideImage_Operation_RoundCorners
	{
		/**
		 * @param WideImage_Image $image
		 * @param int $radius
		 * @param int $color
		 * @param int $smoothness
		 * @return WideImage_Image
		 */
		function execute($image, $radius, $color, $smoothness, $corners)
		{
			if ($smoothness < 1)
				$sample_ratio = 1;
			elseif ($smoothness > 16)
				$sample_ratio = 16;
			else
				$sample_ratio = $smoothness;
			
			$corner = WideImage::createTrueColorImage($radius * $sample_ratio, $radius * $sample_ratio);
			if ($color === null)
			{
				imagepalettecopy($corner->getHandle(), $image->getHandle());
				$bg_color = $corner->allocateColor(0, 0, 0);
				
				$corner->fill(0, 0, $bg_color);
				$fg_color = $corner->allocateColor(255, 255, 255);
				$corner->getCanvas()->filledEllipse($radius * $sample_ratio, $radius * $sample_ratio, $radius * 2 * $sample_ratio, $radius * 2 * $sample_ratio, $fg_color);
				$corner = $corner->resize($radius, $radius);
				
				$result = $image->asTrueColor();
				
				$tc = $result->getTransparentColor();
				if ($tc == -1)
				{
					$tc = $result->allocateColorAlpha(255, 255, 255, 127);
					imagecolortransparent($result->getHandle(), $tc);
					$result->setTransparentColor($tc);
				}
				
				if ($corners & WideImage::SIDE_TOP_LEFT || $corners & WideImage::SIDE_LEFT || $corners & WideImage::SIDE_TOP)
					$result = $result->applyMask($corner, -1, -1);
				
				$corner = $corner->rotate(90);
				if ($corners & WideImage::SIDE_TOP_RIGHT || $corners & WideImage::SIDE_TOP || $corners & WideImage::SIDE_RIGHT)
					$result = $result->applyMask($corner, $result->getWidth() - $corner->getWidth() + 1, -1, 100);
				
				$corner = $corner->rotate(90);
				if ($corners & WideImage::SIDE_BOTTOM_RIGHT || $corners & WideImage::SIDE_RIGHT || $corners & WideImage::SIDE_BOTTOM)
					$result = $result->applyMask($corner, $result->getWidth() - $corner->getWidth() + 1, $result->getHeight() - $corner->getHeight() + 1, 100);
				
				$corner = $corner->rotate(90);
				if ($corners & WideImage::SIDE_BOTTOM_LEFT || $corners & WideImage::SIDE_LEFT || $corners & WideImage::SIDE_BOTTOM)
					$result = $result->applyMask($corner, -1, $result->getHeight() - $corner->getHeight() + 1, 100);
				
				return $result;
			}
			else
			{
				$bg_color = $color;
				
				$corner->fill(0, 0, $bg_color);
				$fg_color = $corner->allocateColorAlpha(127, 127, 127, 127);
				$corner->getCanvas()->filledEllipse($radius * $sample_ratio, $radius * $sample_ratio, $radius * 2 * $sample_ratio, $radius * 2 * $sample_ratio, $fg_color);
				$corner = $corner->resize($radius, $radius);
				
				$result = $image->copy();
				if ($corners & WideImage::SIDE_TOP_LEFT || $corners & WideImage::SIDE_LEFT || $corners & WideImage::SIDE_TOP)
					$result = $result->merge($corner, -1, -1, 100);
				
				$corner = $corner->rotate(90);
				if ($corners & WideImage::SIDE_TOP_RIGHT || $corners & WideImage::SIDE_TOP || $corners & WideImage::SIDE_RIGHT)
					$result = $result->merge($corner, $result->getWidth() - $corner->getWidth() + 1, -1, 100);
				
				$corner = $corner->rotate(90);
				if ($corners & WideImage::SIDE_BOTTOM_RIGHT || $corners & WideImage::SIDE_RIGHT || $corners & WideImage::SIDE_BOTTOM)
					$result = $result->merge($corner, $result->getWidth() - $corner->getWidth() + 1, $result->getHeight() - $corner->getHeight() + 1, 100);
				
				$corner = $corner->rotate(90);
				if ($corners & WideImage::SIDE_BOTTOM_LEFT || $corners & WideImage::SIDE_LEFT || $corners & WideImage::SIDE_BOTTOM)
					$result = $result->merge($corner, -1, $result->getHeight() - $corner->getHeight() + 1, 100);
				
				return $result;
			}
		}
	}
