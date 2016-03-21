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
	 * An Exception for when an invalid fit method is passed
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_InvalidFitMethodException extends WideImage_Exception {}
	/**
	 * An Exception for when an invalid resize dimensions are passed
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_InvalidResizeDimensionException extends WideImage_Exception {}
	
	/**
	 * Resize operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_Resize
	{
		/**
		 * Prepares and corrects smart coordinates
		 *
		 * @param WideImage_Image $img
		 * @param smart_coordinate $width
		 * @param smart_coordinate $height
		 * @param string $fit
		 * @return array
		 */
		protected function prepareDimensions($img, $width, $height, $fit)
		{
			if ($width === null && $height === null)
			{
				$width = $img->getWidth();
				$height = $img->getHeight();
			}
			
			if ($width !== null)
				$width = WideImage_Coordinate::fix($width, $img->getWidth());
			
			if ($height !== null)
				$height = WideImage_Coordinate::fix($height, $img->getHeight());
			
			if ($width === null)
				$width = floor($img->getWidth() * $height / $img->getHeight());
			
			if ($height === null)
				$height = floor($img->getHeight() * $width / $img->getWidth());
			
			if ($width === 0 || $height === 0)
				return array('width' => 0, 'height' => 0);
			
			if ($fit == null)
				$fit = 'inside';
			
			$dim = array();
			if ($fit == 'fill')
			{
				$dim['width'] = $width;
				$dim['height'] = $height;
			}
			elseif ($fit == 'inside' || $fit == 'outside')
			{
				$rx = $img->getWidth() / $width;
				$ry = $img->getHeight() / $height;
				
				if ($fit == 'inside')
					$ratio = ($rx > $ry) ? $rx : $ry;
				else
					$ratio = ($rx < $ry) ? $rx : $ry;
				
				$dim['width'] = round($img->getWidth() / $ratio);
				$dim['height'] = round($img->getHeight() / $ratio);
			}
			else
				throw new WideImage_Operation_InvalidFitMethodException("{$fit} is not a valid resize-fit method.");
			
			return $dim;
		}
		
		/**
		 * Returns a resized image
		 *
		 * @param WideImage_Image $img
		 * @param smart_coordinate $width
		 * @param smart_coordinate $height
		 * @param string $fit
		 * @param string $scale
		 * @return WideImage_Image
		 */
		function execute($img, $width, $height, $fit, $scale)
		{
			$dim = $this->prepareDimensions($img, $width, $height, $fit);
			if (($scale === 'down' && ($dim['width'] >= $img->getWidth() && $dim['height'] >= $img->getHeight())) ||
				($scale === 'up' && ($dim['width'] <= $img->getWidth() && $dim['height'] <= $img->getHeight())))
				$dim = array('width' => $img->getWidth(), 'height' => $img->getHeight());
			
			if ($dim['width'] <= 0 || $dim['height'] <= 0)
				throw new WideImage_Operation_InvalidResizeDimensionException("Both dimensions must be larger than 0.");
			
			if ($img->isTransparent() || $img instanceof WideImage_PaletteImage)
			{
				$new = WideImage_PaletteImage::create($dim['width'], $dim['height']);
				$new->copyTransparencyFrom($img);
				if (!imagecopyresized(
						$new->getHandle(), 
						$img->getHandle(), 
						0, 0, 0, 0, 
						$new->getWidth(), 
						$new->getHeight(), 
						$img->getWidth(), 
						$img->getHeight()))
					throw new WideImage_GDFunctionResultException("imagecopyresized() returned false");
			}
			else
			{
				$new = WideImage_TrueColorImage::create($dim['width'], $dim['height']);
				$new->alphaBlending(false);
				$new->saveAlpha(true);
				if (!imagecopyresampled(
						$new->getHandle(), 
						$img->getHandle(), 
						0, 0, 0, 0, 
						$new->getWidth(), 
						$new->getHeight(), 
						$img->getWidth(), 
						$img->getHeight()))
					throw new WideImage_GDFunctionResultException("imagecopyresampled() returned false");
				$new->alphaBlending(true);
			}
			return $new;
		}
	}
