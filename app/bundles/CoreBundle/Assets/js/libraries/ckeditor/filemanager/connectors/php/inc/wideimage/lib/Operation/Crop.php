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
	 * Crop operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_Crop
	{
		/**
		 * Returns a cropped image
		 *
		 * @param WideImage_Image $img
		 * @param smart_coordinate $left
		 * @param smart_coordinate $top
		 * @param smart_coordinate $width
		 * @param smart_coordinate $height
		 * @return WideImage_Image
		 */
		function execute($img, $left, $top, $width, $height)
		{
			$width = WideImage_Coordinate::fix($width, $img->getWidth(), $width);
			$height = WideImage_Coordinate::fix($height, $img->getHeight(), $height);
			$left = WideImage_Coordinate::fix($left, $img->getWidth(), $width);
			$top = WideImage_Coordinate::fix($top, $img->getHeight(), $height);
			if ($left < 0)
			{
				$width = $left + $width;
				$left = 0;
			}
			
			if ($width > $img->getWidth() - $left)
				$width = $img->getWidth() - $left;
			
			if ($top < 0)
			{
				$height = $top + $height;
				$top = 0;
			}
			
			if ($height > $img->getHeight() - $top)
				$height = $img->getHeight() - $top;
			
			if ($width <= 0 || $height <= 0)
				throw new WideImage_Exception("Can't crop outside of an image.");
			
			$new = $img->doCreate($width, $height);
			
			if ($img->isTransparent() || $img instanceof WideImage_PaletteImage)
			{
				$new->copyTransparencyFrom($img);
				if (!imagecopyresized($new->getHandle(), $img->getHandle(), 0, 0, $left, $top, $width, $height, $width, $height))
					throw new WideImage_GDFunctionResultException("imagecopyresized() returned false");
			}
			else
			{
				$new->alphaBlending(false);
				$new->saveAlpha(true);
				if (!imagecopyresampled($new->getHandle(), $img->getHandle(), 0, 0, $left, $top, $width, $height, $width, $height))
					throw new WideImage_GDFunctionResultException("imagecopyresampled() returned false");
			}
			return $new;
		}
	}
