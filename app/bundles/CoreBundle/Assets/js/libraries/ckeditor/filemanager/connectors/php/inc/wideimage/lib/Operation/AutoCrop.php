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
	 * AutoCrop operation
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_AutoCrop
	{
		/**
		 * Executes the auto-crop operation on the $img
		 * 
		 * @param WideImage_Image $img 
		 * @param int $rgb_threshold The difference in RGB from $base_color
		 * @param int $pixel_cutoff The number of pixels on each border that must be over $rgb_threshold
		 * @param int $base_color The color that will get cropped
		 * @return WideImage_Image resulting auto-cropped image
		 */
		function execute($img, $margin, $rgb_threshold, $pixel_cutoff, $base_color)
		{
			$margin = intval($margin);
			
			$rgb_threshold = intval($rgb_threshold);
			if ($rgb_threshold < 0)
				$rgb_threshold = 0;
			
			$pixel_cutoff = intval($pixel_cutoff);
			if ($pixel_cutoff <= 1)
				$pixel_cutoff = 1;
			
			if ($base_color === null)
				$rgb_base = $img->getRGBAt(0, 0);
			else
			{
				if ($base_color < 0)
					return $img->copy();
				
				$rgb_base = $img->getColorRGB($base_color);
			}
			
			$cut_rect = array('left' => 0, 'top' => 0, 'right' => $img->getWidth() - 1, 'bottom' => $img->getHeight() - 1);
			
			for ($y = 0; $y <= $cut_rect['bottom']; $y++)
			{
				$count = 0;
				for ($x = 0; $x <= $cut_rect['right']; $x++)
				{
					$rgb = $img->getRGBAt($x, $y);
					$diff = abs($rgb['red'] - $rgb_base['red']) + abs($rgb['green'] - $rgb_base['green']) + abs($rgb['blue'] - $rgb_base['blue']);
					if ($diff > $rgb_threshold)
					{
						$count++;
						if ($count >= $pixel_cutoff)
						{
							$cut_rect['top'] = $y;
							break 2;
						}
					}
				}
			}
			
			for ($y = $img->getHeight() - 1; $y >= $cut_rect['top']; $y--)
			{
				$count = 0;
				for ($x = 0; $x <= $cut_rect['right']; $x++)
				{
					$rgb = $img->getRGBAt($x, $y);
					$diff = abs($rgb['red'] - $rgb_base['red']) + abs($rgb['green'] - $rgb_base['green']) + abs($rgb['blue'] - $rgb_base['blue']);
					if ($diff > $rgb_threshold)
					{
						$count++;
						if ($count >= $pixel_cutoff)
						{
							$cut_rect['bottom'] = $y;
							break 2;
						}
					}
				}
			}
			
			for ($x = 0; $x <= $cut_rect['right']; $x++)
			{
				$count = 0;
				for ($y = $cut_rect['top']; $y <= $cut_rect['bottom']; $y++)
				{
					$rgb = $img->getRGBAt($x, $y);
					$diff = abs($rgb['red'] - $rgb_base['red']) + abs($rgb['green'] - $rgb_base['green']) + abs($rgb['blue'] - $rgb_base['blue']);
					if ($diff > $rgb_threshold)
					{
						$count++;
						if ($count >= $pixel_cutoff)
						{
							$cut_rect['left'] = $x;
							break 2;
						}
					}
				}
			}
			
			for ($x = $cut_rect['right']; $x >= $cut_rect['left']; $x--)
			{
				$count = 0;
				for ($y = $cut_rect['top']; $y <= $cut_rect['bottom']; $y++)
				{
					$rgb = $img->getRGBAt($x, $y);
					$diff = abs($rgb['red'] - $rgb_base['red']) + abs($rgb['green'] - $rgb_base['green']) + abs($rgb['blue'] - $rgb_base['blue']);
					if ($diff > $rgb_threshold)
					{
						$count++;
						if ($count >= $pixel_cutoff)
						{
							$cut_rect['right'] = $x;
							break 2;
						}
					}
				}
			}
			
			$cut_rect = array(
					'left' => $cut_rect['left'] - $margin,
					'top' => $cut_rect['top'] - $margin,
					'right' => $cut_rect['right'] + $margin,
					'bottom' => $cut_rect['bottom'] + $margin
				);
			
			if ($cut_rect['left'] < 0)
				$cut_rect['left'] = 0;
			
			if ($cut_rect['top'] < 0)
				$cut_rect['top'] = 0;
			
			if ($cut_rect['right'] >= $img->getWidth())
				$cut_rect['right'] = $img->getWidth() - 1;
			
			if ($cut_rect['bottom'] >= $img->getHeight())
				$cut_rect['bottom'] = $img->getHeight() - 1;
			
			return $img->crop($cut_rect['left'], $cut_rect['top'], $cut_rect['right'] - $cut_rect['left'] + 1, $cut_rect['bottom'] - $cut_rect['top'] + 1);
		}
	}
