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
  **/
	
	/**
	 * A class for truecolor image objects
	 * 
	 * @package WideImage
	 */
	class WideImage_TrueColorImage extends WideImage_Image
	{
		/**
		 * Creates the object
		 *
		 * @param resource $handle
		 */
		function __construct($handle)
		{
			parent::__construct($handle);
			$this->alphaBlending(false);
			$this->saveAlpha(true);
		}
		
		/**
		 * Factory method that creates a true-color image object
		 *
		 * @param int $width
		 * @param int $height
		 * @return WideImage_TrueColorImage
		 */
		static function create($width, $height)
		{
			if ($width * $height <= 0 || $width < 0)
				throw new WideImage_InvalidImageDimensionException("Can't create an image with dimensions [$width, $height].");
			
			return new WideImage_TrueColorImage(imagecreatetruecolor($width, $height));
		}
		
		function doCreate($width, $height)
		{
			return self::create($width, $height);
		}
		
		function isTrueColor()
		{
			return true;
		}
		
		/**
		 * Sets alpha blending mode via imagealphablending()
		 *
		 * @param bool $mode
		 * @return bool
		 */
		function alphaBlending($mode)
		{
			return imagealphablending($this->handle, $mode);
		}
		
		/**
		 * Toggle if alpha channel should be saved with the image via imagesavealpha()
		 *
		 * @param bool $on
		 * @return bool
		 */
		function saveAlpha($on)
		{
			return imagesavealpha($this->handle, $on);
		}
		
		/**
		 * Allocates a color and returns its index
		 * 
		 * This method accepts either each component as an integer value,
		 * or an associative array that holds the color's components in keys
		 * 'red', 'green', 'blue', 'alpha'.
		 *
		 * @param mixed $R
		 * @param int $G
		 * @param int $B
		 * @param int $A
		 * @return int
		 */
		function allocateColorAlpha($R, $G = null, $B = null, $A = null)
		{
			if (is_array($R))
				return imageColorAllocateAlpha($this->handle, $R['red'], $R['green'], $R['blue'], $R['alpha']);
			else
				return imageColorAllocateAlpha($this->handle, $R, $G, $B, $A);
		}
		
		/**
		 * @see WideImage_Image#asPalette($nColors, $dither, $matchPalette)
		 */
		function asPalette($nColors = 255, $dither = null, $matchPalette = true)
		{
			$nColors = intval($nColors);
			if ($nColors < 1)
				$nColors = 1;
			elseif ($nColors > 255)
				$nColors = 255;
			
			if ($dither === null)
				$dither = $this->isTransparent();
			
			$temp = $this->copy();
			imagetruecolortopalette($temp->handle, $dither, $nColors);
			if ($matchPalette == true && function_exists('imagecolormatch'))
				imagecolormatch($this->handle, $temp->handle);
			
			// The code below isn't working properly; it corrupts transparency on some palette->tc->palette conversions.
			// Why is this code here?
			/*
			if ($this->isTransparent())
			{
				$trgb = $this->getTransparentColorRGB();
				$tci = $temp->getClosestColor($trgb);
				$temp->setTransparentColor($tci);
			}
			/**/
			
			$temp->releaseHandle();
			return new WideImage_PaletteImage($temp->handle);
		}
		
		/**
		 * Returns the index of the color that best match the given color components
		 *
		 * This method accepts either each component as an integer value,
		 * or an associative array that holds the color's components in keys
		 * 'red', 'green', 'blue', 'alpha'.
		 *
		 * @param mixed $R Red component value or an associative array
		 * @param int $G Green component
		 * @param int $B Blue component
		 * @param int $A Alpha component
		 * @return int The color index
		 */
		function getClosestColorAlpha($R, $G = null, $B = null, $A = null)
		{
			if (is_array($R))
				return imagecolorclosestalpha($this->handle, $R['red'], $R['green'], $R['blue'], $R['alpha']);
			else
				return imagecolorclosestalpha($this->handle, $R, $G, $B, $A);
		}
		
		/**
		 * Returns the index of the color that exactly match the given color components
		 *
		 * This method accepts either each component as an integer value,
		 * or an associative array that holds the color's components in keys
		 * 'red', 'green', 'blue', 'alpha'.
		 *
		 * @param mixed $R Red component value or an associative array
		 * @param int $G Green component
		 * @param int $B Blue component
		 * @param int $A Alpha component
		 * @return int The color index
		 */
		function getExactColorAlpha($R, $G = null, $B = null, $A = null)
		{
			if (is_array($R))
				return imagecolorexactalpha($this->handle, $R['red'], $R['green'], $R['blue'], $R['alpha']);
			else
				return imagecolorexactalpha($this->handle, $R, $G, $B, $A);
		}
		
		/**
		 * @see WideImage_Image#getChannels()
		 */
		function getChannels()
		{
			$args = func_get_args();
			if (count($args) == 1 && is_array($args[0]))
				$args = $args[0];
			return WideImage_OperationFactory::get('CopyChannelsTrueColor')->execute($this, $args);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see WideImage_Image#copyNoAlpha()
		 */
		function copyNoAlpha()
		{
			$prev = $this->saveAlpha(false);
			$result = WideImage_Image::loadFromString($this->asString('png'));
			$this->saveAlpha($prev);
			//$result->releaseHandle();
			return $result;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see WideImage_Image#asTrueColor()
		 */
		function asTrueColor()
		{
			return $this->copy();
		}
	}
