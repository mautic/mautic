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
	 * CopyChannelsTrueColor operation class
	 * 
	 * Used to perform CopyChannels operation on truecolor images
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_CopyChannelsTrueColor
	{
		/**
		 * Returns an image with only specified channels copied
		 * 
		 * @param WideImage_Image $img
		 * @param array $channels
		 * @return WideImage_Image
		 */
		function execute($img, $channels)
		{
			$blank = array('red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0);
			
			$width = $img->getWidth();
			$height = $img->getHeight();
			$copy = WideImage_TrueColorImage::create($width, $height);
			
			if (count($channels) > 0)
				for ($x = 0; $x < $width; $x++)
					for ($y = 0; $y < $height; $y++)
					{
						$RGBA = $img->getRGBAt($x, $y);
						$newRGBA = $blank;
						foreach ($channels as $channel)
							$newRGBA[$channel] = $RGBA[$channel];
						
						$color = $copy->getExactColorAlpha($newRGBA);
						if ($color == -1)
							$color = $copy->allocateColorAlpha($newRGBA);
						
						$copy->setColorAt($x, $y, $color);
					}
			
			return $copy;
		}
	}
