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
	 * CopyChannelsPalette operation class
	 * 
	 * This operation is intended to be used on palette images
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_CopyChannelsPalette
	{
		/**
		 * Returns an image with only specified channels copied
		 *
		 * @param WideImage_PaletteImage $img
		 * @param array $channels
		 * @return WideImage_PaletteImage
		 */
		function execute($img, $channels)
		{
			$blank = array('red' => 0, 'green' => 0, 'blue' => 0);
			if (isset($channels['alpha']))
				unset($channels['alpha']);
			
			$width = $img->getWidth();
			$height = $img->getHeight();
			$copy = WideImage_PaletteImage::create($width, $height);
			
			if ($img->isTransparent())
			{
				$otci = $img->getTransparentColor();
				$TRGB = $img->getColorRGB($otci);
				$tci = $copy->allocateColor($TRGB);
			}
			else
			{
				$otci = null;
				$tci = null;
			}
			
			for ($x = 0; $x < $width; $x++)
				for ($y = 0; $y < $height; $y++)
				{
					$ci = $img->getColorAt($x, $y);
					if ($ci === $otci)
					{
						$copy->setColorAt($x, $y, $tci);
						continue;
					}
					$RGB = $img->getColorRGB($ci);
					
					$newRGB = $blank;
					foreach ($channels as $channel)
						$newRGB[$channel] = $RGB[$channel];
					
					$color = $copy->getExactColor($newRGB);
					if ($color == -1)
						$color = $copy->allocateColor($newRGB);
					
					$copy->setColorAt($x, $y, $color);
				}
			
			if ($img->isTransparent())
				$copy->setTransparentColor($tci);
			
			return $copy;
		}
	}
