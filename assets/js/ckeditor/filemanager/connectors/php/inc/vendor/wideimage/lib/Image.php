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

	* @package WideImage
  **/
	
	/**
	 * Thrown when an invalid dimension is passed for some operations
	 * 
	 * @package Exceptions
	 */
	class WideImage_InvalidImageDimensionException extends WideImage_Exception {}
	
	/**
	 * Thrown when an image can't be saved (returns false by the mapper)
	 * 
	 * @package Exceptions
	 */
	class WideImage_UnknownErrorWhileMappingException extends WideImage_Exception {}
	
	/**
	 * Base class for images
	 * 
	 * @package WideImage
	 */
	abstract class WideImage_Image
	{
		/**
		 * Holds the image resource
		 * @var resource
		 */
		protected $handle = null;
		
		/**
		 * Flag that determines if WideImage should call imagedestroy() upon object destruction
		 * @var bool
		 */
		protected $handleReleased = false;
		
		/**
		 * Canvas object
		 * @var WideImage_Canvas
		 */
		protected $canvas = null;
		
		/**
		 * @var string
		 */
		protected $sdata = null;
		
		/**
		 * The base class constructor
		 *
		 * @param resource $handle Image handle (GD2 resource)
		 */
		function __construct($handle)
		{
			WideImage::assertValidImageHandle($handle);
			$this->handle = $handle;
		}
		
		/**
		 * Cleanup
		 * 
		 * Destroys the handle via WideImage_Image::destroy() when called by the GC.
		 */
		function __destruct()
		{
			$this->destroy();
		}
		
		/**
		 * This method destroy the image handle, and releases the image resource.
		 * 
		 * After this is called, the object doesn't hold a valid image any more.
		 * No operation should be called after that.
		 */
		function destroy()
		{
			if ($this->isValid() && !$this->handleReleased)
				imagedestroy($this->handle);
			
			$this->handle = null;
		}
		
		/**
		 * Returns the GD image resource
		 * 
		 * @return resource GD image resource
		 */
		function getHandle()
		{
			return $this->handle;
		}
		
		/**
		 * @return bool True, if the image object holds a valid GD image, false otherwise
		 */
		function isValid()
		{
			return WideImage::isValidImageHandle($this->handle);
		}
		
		/**
		 * Releases the handle
		 */
		function releaseHandle()
		{
			$this->handleReleased = true;
		}
		
		/**
		 * Saves an image to a file
		 * 
		 * The file type is recognized from the $uri. If you save to a GIF8, truecolor images
		 * are automatically converted to palette.
		 * 
		 * This method supports additional parameters: quality (for jpeg images) and 
		 * compression quality and filters (for png images). See http://www.php.net/imagejpeg and
		 * http://www.php.net/imagepng for details.
		 * 
		 * Examples:
		 * <code>
		 * // save to a GIF
		 * $image->saveToFile('image.gif');
		 * 
		 * // save to a PNG with compression=7 and no filters
		 * $image->saveToFile('image.png', 7, PNG_NO_FILTER);
		 * 
		 * // save to a JPEG with quality=80
		 * $image->saveToFile('image.jpg', 80);
		 * 
		 * // save to a JPEG with default quality=100
		 * $image->saveToFile('image.jpg');
		 * </code>
		 * 
		 * @param string $uri File location
		 */
		function saveToFile($uri)
		{
			$mapper = WideImage_MapperFactory::selectMapper($uri, null);
			$args = func_get_args();
			array_unshift($args, $this->getHandle());
			$res = call_user_func_array(array($mapper, 'save'), $args);
			if (!$res)
				throw new WideImage_UnknownErrorWhileMappingException(get_class($mapper) . " returned an invalid result while saving to $uri");
		}
		
		/**
		 * Returns binary string with image data in format specified by $format
		 * 
		 * Additional parameters may be passed to the function. See WideImage_Image::saveToFile() for more details.
		 * 
		 * @param string $format The format of the image
		 * @return string The binary image data in specified format
		 */
		function asString($format)
		{
			ob_start();
			$args = func_get_args();
			$args[0] = null;
			array_unshift($args, $this->getHandle());
			
			$mapper = WideImage_MapperFactory::selectMapper(null, $format);
			$res = call_user_func_array(array($mapper, 'save'), $args);
			if (!$res)
				throw new WideImage_UnknownErrorWhileMappingException(get_class($mapper) . " returned an invalid result while writing the image data");
			
			return ob_get_clean();
		}
		
		/**
		 * Output a header to browser.
		 * 
		 * @param $name Name of the header
		 * @param $data Data
		 */
		protected function writeHeader($name, $data)
		{
			header($name . ": " . $data);
		}
		
		/**
		 * Outputs the image to browser
		 * 
		 * Sets headers Content-length and Content-type, and echoes the image in the specified format.
		 * All other headers (such as Content-disposition) must be added manually. 
		 * 
		 * Example:
		 * <code>
		 * WideImage::load('image1.png')->resize(100, 100)->output('gif');
		 * </code>
		 * 
		 * @param string $format Image format
		 */
		function output($format)
		{
			$args = func_get_args();
			$data = call_user_func_array(array($this, 'asString'), $args);
			
			$this->writeHeader('Content-length', strlen($data));
			$this->writeHeader('Content-type', WideImage_MapperFactory::mimeType($format));
			echo $data;
		}
		
		/**
		 * @return int Image width
		 */
		function getWidth()
		{
			return imagesx($this->handle);
		}
		
		/**
		 * @return int Image height
		 */
		function getHeight()
		{
			return imagesy($this->handle);
		}
		
		/**
		 * Allocate a color by RGB values.
		 * 
		 * @param mixed $R Red-component value or an RGB array (with red, green, blue keys)
		 * @param int $G If $R is int, this is the green component
		 * @param int $B If $R is int, this is the blue component
		 * @return int Image color index
		 */
		function allocateColor($R, $G = null, $B = null)
		{
			if (is_array($R))
				return imageColorAllocate($this->handle, $R['red'], $R['green'], $R['blue']);
			else
				return imageColorAllocate($this->handle, $R, $G, $B);
		}
		
		/**
		 * @return bool True if the image is transparent, false otherwise
		 */
		function isTransparent()
		{
			return $this->getTransparentColor() >= 0;
		}
		
		/**
		 * @return int Transparent color index
		 */
		function getTransparentColor()
		{
			return imagecolortransparent($this->handle);
		}
		
		/**
		 * Sets the current transparent color index. Only makes sense for palette images (8-bit).
		 * 
		 * @param int $color Transparent color index
		 */
		function setTransparentColor($color)
		{
			return imagecolortransparent($this->handle, $color);
		}
		
		/**
		 * Returns a RGB array of the transparent color or null if none.
		 * 
		 * @return mixed Transparent color RGBA array
		 */
		function getTransparentColorRGB()
		{
			$total = imagecolorstotal($this->handle);
			$tc = $this->getTransparentColor();
			
			if ($tc >= $total && $total > 0)
				return null;
			else
				return $this->getColorRGB($tc);
		}
		
		/**
		 * Returns a RGBA array for pixel at $x, $y
		 * 
		 * @param int $x
		 * @param int $y
		 * @return array RGB array 
		 */
		function getRGBAt($x, $y)
		{
			return $this->getColorRGB($this->getColorAt($x, $y));
		}
		
		/**
		 * Writes a pixel at the designated coordinates
		 * 
		 * Takes an associative array of colours and uses getExactColor() to
		 * retrieve the exact index color to write to the image with.
		 *
		 * @param int $x
		 * @param int $y
		 * @param array $color
		 */
		function setRGBAt($x, $y, $color)
		{
			$this->setColorAt($x, $y, $this->getExactColor($color));
		}
		
		/**
		 * Returns a color's RGB
		 * 
		 * @param int $colorIndex Color index
		 * @return mixed RGBA array for a color with index $colorIndex
		 */
		function getColorRGB($colorIndex)
		{
			return imageColorsForIndex($this->handle, $colorIndex);
		}
		
		/**
		 * Returns an index of the color at $x, $y
		 * 
		 * @param int $x
		 * @param int $y
		 * @return int Color index for a pixel at $x, $y
		 */
		function getColorAt($x, $y)
		{
			return imagecolorat($this->handle, $x, $y);
		}
		
		/**
		 * Set the color index $color to a pixel at $x, $y
		 * 
		 * @param int $x
		 * @param int $y
		 * @param int $color Color index
		 */
		function setColorAt($x, $y, $color)
		{
			return imagesetpixel($this->handle, $x, $y, $color);
		}
		
		/**
		 * Returns closest color index that matches the given RGB value. Uses
		 * PHP's imagecolorclosest()
		 * 
		 * @param mixed $R Red or RGBA array
		 * @param int $G Green component (or null if $R is an RGB array)
		 * @param int $B Blue component (or null if $R is an RGB array)
		 * @return int Color index
		 */
		function getClosestColor($R, $G = null, $B = null)
		{
			if (is_array($R))
				return imagecolorclosest($this->handle, $R['red'], $R['green'], $R['blue']);
			else
				return imagecolorclosest($this->handle, $R, $G, $B);
		}
		
		/**
		 * Returns the color index that exactly matches the given RGB value. Uses
		 * PHP's imagecolorexact()
		 * 
		 * @param mixed $R Red or RGBA array
		 * @param int $G Green component (or null if $R is an RGB array)
		 * @param int $B Blue component (or null if $R is an RGB array)
		 * @return int Color index
		 */
		function getExactColor($R, $G = null, $B = null)
		{
			if (is_array($R))
				return imagecolorexact($this->handle, $R['red'], $R['green'], $R['blue']);
			else
				return imagecolorexact($this->handle, $R, $G, $B);
		}
		
		/**
		 * Copies transparency information from $sourceImage. Optionally fills
		 * the image with the transparent color at (0, 0).
		 * 
		 * @param object $sourceImage
		 * @param bool $fill True if you want to fill the image with transparent color
		 */
		function copyTransparencyFrom($sourceImage, $fill = true)
		{
			if ($sourceImage->isTransparent())
			{
				$rgba = $sourceImage->getTransparentColorRGB();
				if ($rgba === null)
					return;
				
				if ($this->isTrueColor())
				{
					$rgba['alpha'] = 127;
					$color = $this->allocateColorAlpha($rgba);
				}
				else
					$color = $this->allocateColor($rgba);
				
				$this->setTransparentColor($color);
				if ($fill)
					$this->fill(0, 0, $color);
			}
		}
		
		/**
		 * Fill the image at ($x, $y) with color index $color
		 * 
		 * @param int $x
		 * @param int $y
		 * @param int $color
		 */
		function fill($x, $y, $color)
		{
			return imagefill($this->handle, $x, $y, $color);
		}
		
		/**
		 * Used internally to create Operation objects
		 *
		 * @param string $name
		 * @return object
		 */
		protected function getOperation($name)
		{
			return WideImage_OperationFactory::get($name);
		}
		
		/**
		 * Returns the image's mask
		 * 
		 * Mask is a greyscale image where the shade defines the alpha channel (black = transparent, white = opaque).
		 * 
		 * For opaque images (JPEG), the result will be white. For images with single-color transparency (GIF, 8-bit PNG), 
		 * the areas with the transparent color will be black. For images with alpha channel transparenct, 
		 * the result will be alpha channel.
		 * 
		 * @return WideImage_Image An image mask
		 **/
		function getMask()
		{
			return $this->getOperation('GetMask')->execute($this);
		}
		
		/**
		 * Resize the image to given dimensions.
		 * 
		 * $width and $height are both smart coordinates. This means that you can pass any of these values in:
		 *   - positive or negative integer (100, -20, ...)
		 *   - positive or negative percent string (30%, -15%, ...)
		 *   - complex coordinate (50% - 20, 15 + 30%, ...)
		 * 
		 * If $width is null, it's calculated proportionally from $height, and vice versa.
		 * 
		 * Example (resize to half-size):
		 * <code>
		 * $smaller = $image->resize('50%');
		 * 
		 * $smaller = $image->resize('100', '100', 'inside', 'down');
		 * is the same as
		 * $smaller = $image->resizeDown(100, 100, 'inside');
		 * </code>
		 * 
		 * @param mixed $width The new width (smart coordinate), or null.
		 * @param mixed $height The new height (smart coordinate), or null.
		 * @param string $fit 'inside', 'outside', 'fill'
		 * @param string $scale 'down', 'up', 'any'
		 * @return WideImage_Image The resized image
		 */
		function resize($width = null, $height = null, $fit = 'inside', $scale = 'any')
		{
			return $this->getOperation('Resize')->execute($this, $width, $height, $fit, $scale);
		}
		
		/**
		 * Same as WideImage_Image::resize(), but the image is only applied if it is larger then the given dimensions.
		 * Otherwise, the resulting image retains the source's dimensions.
		 * 
		 * @param int $width New width, smart coordinate
		 * @param int $height New height, smart coordinate
		 * @param string $fit 'inside', 'outside', 'fill'
		 * @return WideImage_Image resized image
		 */
		function resizeDown($width = null, $height = null, $fit = 'inside')
		{
			return $this->resize($width, $height, $fit, 'down');
		}
		
		/**
		 * Same as WideImage_Image::resize(), but the image is only applied if it is smaller then the given dimensions.
		 * Otherwise, the resulting image retains the source's dimensions.
		 * 
		 * @param int $width New width, smart coordinate
		 * @param int $height New height, smart coordinate
		 * @param string $fit 'inside', 'outside', 'fill'
		 * @return WideImage_Image resized image
		 */
		function resizeUp($width = null, $height = null, $fit = 'inside')
		{
			return $this->resize($width, $height, $fit, 'up');
		}
		
		/**
		 * Rotate the image for angle $angle clockwise.
		 * 
		 * Preserves transparency. Has issues when saving to a BMP.
		 * 
		 * @param int $angle Angle in degrees, clock-wise
		 * @param int $bgColor color of the new background
		 * @param bool $ignoreTransparent
		 * @return WideImage_Image The rotated image
		 */
		function rotate($angle, $bgColor = null, $ignoreTransparent = true)
		{
			return $this->getOperation('Rotate')->execute($this, $angle, $bgColor, $ignoreTransparent);
		}
		
		/**
		 * This method lays the overlay (watermark) on the image.
		 * 
		 * Hint: if the overlay is a truecolor image with alpha channel, you should leave $pct at 100.
		 * 
		 * This operation supports alignment notation in coordinates:
		 * <code>
		 * $watermark = WideImage::load('logo.gif');
		 * $base = WideImage::load('picture.jpg');
		 * $result = $base->merge($watermark, "right - 10", "bottom - 10", 50);
		 * // applies a logo aligned to bottom-right corner with a 10 pixel margin
		 * </code>
		 * 
		 * @param WideImage_Image $overlay The overlay image
		 * @param mixed $left Left position of the overlay, smart coordinate
		 * @param mixed $top Top position of the overlay, smart coordinate
		 * @param int $pct The opacity of the overlay
		 * @return WideImage_Image The merged image
		 */
		function merge($overlay, $left = 0, $top = 0, $pct = 100)
		{
			return $this->getOperation('Merge')->execute($this, $overlay, $left, $top, $pct);
		}
		
		/**
		 * Resizes the canvas of the image, but doesn't scale the content of the image
		 * 
		 * This operation creates an empty canvas with dimensions $width x $height, filled with 
		 * background color $bg_color and draws the original image onto it at position [$pos_x, $pos_y].
		 * 
		 * Arguments $width, $height, $pos_x and $pos_y are all smart coordinates. $width and $height are 
		 * relative to the current image size, $pos_x and $pos_y are relative to the newly calculated
		 * canvas size. This can be confusing, but it makes sense. See the example below.
		 * 
		 * The example below loads a 100x150 image and then resizes its canvas to 200% x 100%+20 
		 * (which evaluates to 200x170). The image is placed at position [10, center+20], which evaluates to [10, 30].
		 * <code>
		 * $image = WideImage::load('someimage.jpg'); // 100x150
		 * $white = $image->allocateColor(255, 255, 255);
		 * $image->resizeCanvas('200%', '100% + 20', 10, 'center+20', $white);
		 * </code>
		 * 
		 * The parameter $merge defines whether the original image should be merged onto the new canvas.
		 * This means it blends transparent color and alpha colors into the background color. If set to false,
		 * the original image is just copied over, preserving the transparency/alpha information.
		 * 
		 * You can set the $scale parameter to limit when to resize the canvas. For example, if you want 
		 * to resize the canvas only if the image is smaller than the new size, but leave the image intact 
		 * if it's larger, set it to 'up'. Likewise, if you want to shrink the canvas, but don't want to 
		 * change images that are already smaller, set it to 'down'. 
		 * 
		 * @param mixed $width Width of the new canvas (smart coordinate, relative to current image width)
		 * @param mixed $height Height of the new canvas (smart coordinate, relative to current image height)
		 * @param mixed $pos_x x-position of the image (smart coordinate, relative to the new width)
		 * @param mixed $pos_y y-position of the image (smart coordinate, relative to the new height)
		 * @param int $bg_color Background color (created with allocateColor or allocateColorAlpha), defaults to null (tries to use a transparent color)
		 * @param string $scale Possible values: 'up' (enlarge only), 'down' (downsize only), 'any' (resize precisely to $width x $height). Defaults to 'any'.
		 * @param bool $merge Merge the original image (flatten alpha channel and transparency) or copy it over (preserve). Defaults to false.
		 * @return WideImage_Image The resulting image with resized canvas
		 */
		function resizeCanvas($width, $height, $pos_x, $pos_y, $bg_color = null, $scale = 'any', $merge = false)
		{
			return $this->getOperation('ResizeCanvas')->execute($this, $width, $height, $pos_x, $pos_y, $bg_color, $scale, $merge);
		}
		
		/**
		 * Returns an image with round corners
		 * 
		 * You can either set the corners' color or set them transparent.
		 * 
		 * Note on $smoothness: 1 means jagged edges, 2 is much better, more than 4 doesn't noticeably improve the quality.
		 * Rendering becomes increasingly slower if you increase smoothness.
		 * 
		 * Example:
		 * <code>
		 * $nice = $ugly->roundCorners(20, $ugly->allocateColor(255, 0, 0), 2);
		 * </code>
		 * 
		 * Use $corners parameter to specify which corners to draw rounded. Possible values are
		 * WideImage::SIDE_TOP_LEFT, WideImage::SIDE_TOP,
		 * WideImage::SIDE_TOP_RIGHT, WideImage::SIDE_RIGHT,
		 * WideImage::SIDE_BOTTOM_RIGHT, WideImage::SIDE_BOTTOM, 
		 * WideImage::SIDE_BOTTOM_LEFT, WideImage::SIDE_LEFT, and WideImage::SIDE_ALL.
		 * You can specify any combination of corners with a + operation, see example below.
		 * 
		 * Example:
		 * <code>
		 * $white = $image->allocateColor(255, 255, 255);
		 * $diagonal_corners = $image->roundCorners(15, $white, 2, WideImage::SIDE_TOP_LEFT + WideImage::SIDE_BOTTOM_RIGHT);
		 * $right_corners = $image->roundCorners(15, $white, 2, WideImage::SIDE_RIGHT);
		 * </code>
		 * 
		 * @param int $radius Radius of the corners
		 * @param int $color The color of corners. If null, corners are rendered transparent (slower than using a solid color).
		 * @param int $smoothness Specify the level of smoothness. Suggested values from 1 to 4.
		 * @param int $corners Specify which corners to draw (defaults to WideImage::SIDE_ALL = all corners)
		 * @return WideImage_Image The resulting image with round corners
		 */
		function roundCorners($radius, $color = null, $smoothness = 2, $corners = 255)
		{
			return $this->getOperation('RoundCorners')->execute($this, $radius, $color, $smoothness, $corners);
		}
		
		/**
		 * Returns an image with applied mask
		 * 
		 * A mask is a grayscale image, where the shade determines the alpha channel. Black is fully transparent
		 * and white is fully opaque.
		 * 
		 * @param WideImage_Image $mask The mask image, greyscale
		 * @param mixed $left Left coordinate, smart coordinate
		 * @param mixed $top Top coordinate, smart coordinate
		 * @return WideImage_Image The resulting image
		 **/
		function applyMask($mask, $left = 0, $top = 0)
		{
			return $this->getOperation('ApplyMask')->execute($this, $mask, $left, $top);
		}
		
		/**
		 * Applies a filter
		 *
		 * @param int $filter One of the IMG_FILTER_* constants
		 * @param int $arg1
		 * @param int $arg2
		 * @param int $arg3
		 * @param int $arg4
		 * @return WideImage_Image
		 */
		function applyFilter($filter, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
		{
			return $this->getOperation('ApplyFilter')->execute($this, $filter, $arg1, $arg2, $arg3, $arg4);
		}
		
		/**
		 * Applies convolution matrix with imageconvolution()
		 *
		 * @param array $matrix
		 * @param float $div
		 * @param float $offset
		 * @return WideImage_Image
		 */
		function applyConvolution($matrix, $div, $offset)
		{
			return $this->getOperation('ApplyConvolution')->execute($this, $matrix, $div, $offset);
		}
		
		/**
		 * Returns a cropped rectangular portion of the image
		 * 
		 * If the rectangle specifies area that is out of bounds, it's limited to the current image bounds.
		 * 
		 * Examples:
		 * <code>
		 * $cropped = $img->crop(10, 10, 150, 200); // crops a 150x200 rect at (10, 10)
		 * $cropped = $img->crop(-100, -50, 100, 50); // crops a 100x50 rect at the right-bottom of the image
		 * $cropped = $img->crop('25%', '25%', '50%', '50%'); // crops a 50%x50% rect from the center of the image
		 * </code>
		 * 
		 * This operation supports alignment notation in left/top coordinates.
		 * Example:
		 * <code>
		 * $cropped = $img->crop("right", "bottom", 100, 200); // crops a 100x200 rect from right bottom
		 * $cropped = $img->crop("center", "middle", 50, 30); // crops a 50x30 from the center of the image
		 * </code>
		 * 
		 * @param mixed $left Left-coordinate of the crop rect, smart coordinate
		 * @param mixed $top Top-coordinate of the crop rect, smart coordinate
		 * @param mixed $width Width of the crop rect, smart coordinate
		 * @param mixed $height Height of the crop rect, smart coordinate
		 * @return WideImage_Image The cropped image
		 **/
		function crop($left = 0, $top = 0, $width = '100%', $height = '100%')
		{
			return $this->getOperation('Crop')->execute($this, $left, $top, $width, $height);
		}
		
		/**
		 * Performs an auto-crop on the image
		 *
		 * The image is auto-cropped from each of four sides. All sides are 
		 * scanned for pixels that differ from $base_color for more than 
		 * $rgb_threshold in absolute RGB difference. If more than $pixel_cutoff 
		 * differentiating pixels are found, that line is considered to be the crop line for the side.
		 * If the line isn't different enough, the algorithm procedes to the next line 
		 * towards the other edge of the image.
		 * 
		 * When the crop rectangle is found, it's enlarged by the $margin value on each of the four sides.
		 *
		 * @param int $margin Margin for the crop rectangle, can be negative.
		 * @param int $rgb_threshold RGB difference which still counts as "same color".
		 * @param int $pixel_cutoff How many pixels need to be different to mark a cut line.
		 * @param int $base_color The base color index. If none specified (or null given), left-top pixel is used.
		 * @return WideImage_Image The cropped image
		 */
		function autoCrop($margin = 0, $rgb_threshold = 0, $pixel_cutoff = 1, $base_color = null)
		{
			return $this->getOperation('AutoCrop')->execute($this, $margin, $rgb_threshold, $pixel_cutoff, $base_color);
		}
		
		/**
		 * Returns a negative of the image
		 *
		 * This operation differs from calling WideImage_Image::applyFilter(IMG_FILTER_NEGATIVE), because it's 8-bit and transparency safe.
		 * This means it will return an 8-bit image, if the source image is 8-bit. If that 8-bit image has a palette transparency,
		 * the resulting image will keep transparency.
		 *
		 * @return WideImage_Image negative of the image
		 */
		function asNegative()
		{
			return $this->getOperation('AsNegative')->execute($this);
		}
		
		/**
		 * Returns a grayscale copy of the image
		 * 
		 * @return WideImage_Image grayscale copy
		 **/
		function asGrayscale()
		{
			return $this->getOperation('AsGrayscale')->execute($this);
		}
		
		/**
		 * Returns a mirrored copy of the image
		 * 
		 * @return WideImage_Image Mirrored copy
		 **/
		function mirror()
		{
			return $this->getOperation('Mirror')->execute($this);
		}
		
		/**
		 * Applies the unsharp filter
		 * 
		 * @param float $amount
		 * @param float $radius
		 * @param float $threshold
		 * @return WideImage_Image Unsharpened copy of the image
		 **/
		function unsharp($amount, $radius, $threshold)
		{
			return $this->getOperation('Unsharp')->execute($this, $amount, $radius, $threshold);
		}
		
		/**
		 * Returns a flipped (mirrored over horizontal line) copy of the image
		 * 
		 * @return WideImage_Image Flipped copy
		 **/
		function flip()
		{
			return $this->getOperation('Flip')->execute($this);
		}
		
		/**
		 * Corrects gamma on the image
		 * 
		 * @param float $inputGamma
		 * @param float $outputGamma
		 * @return WideImage_Image Image with corrected gamma
		 **/
		function correctGamma($inputGamma, $outputGamma)
		{
			return $this->getOperation('CorrectGamma')->execute($this, $inputGamma, $outputGamma);
		}
		
		/**
		 * Adds noise to the image
		 * 
		 * @author Tomasz Kapusta
		 * 
		 * @param int $amount Number of noise pixels to add
		 * @param string $type Type of noise 'salt&pepper', 'color' or 'mono'
		 * @return WideImage_Image Image with noise added
		 **/
		function addNoise($amount, $type)
		{
			return $this->getOperation('AddNoise')->execute($this, $amount, $type);
		}
		
		/**
		 * Used internally to execute operations
		 *
		 * @param string $name
		 * @param array $args
		 * @return WideImage_Image
		 */
		function __call($name, $args)
		{
			$op = $this->getOperation($name);
			array_unshift($args, $this);
			return call_user_func_array(array($op, 'execute'), $args);
		}
		
		/**
		 * Returns an image in GIF or PNG format
		 *
		 * @return string
		 */
		function __toString()
		{
			if ($this->isTransparent())
				return $this->asString('gif');
			else
				return $this->asString('png');
		}
		
		/**
		 * Returns a copy of the image object
		 * 
		 * @return WideImage_Image The copy
		 **/
		function copy()
		{
			$dest = $this->doCreate($this->getWidth(), $this->getHeight());
			$dest->copyTransparencyFrom($this, true);
			$this->copyTo($dest, 0, 0);
			return $dest;
		}
		
		/**
		 * Copies this image onto another image
		 * 
		 * @param WideImage_Image $dest
		 * @param int $left
		 * @param int $top
		 **/
		function copyTo($dest, $left = 0, $top = 0)
		{
			if (!imagecopy($dest->getHandle(), $this->handle, $left, $top, 0, 0, $this->getWidth(), $this->getHeight()))
				throw new WideImage_GDFunctionResultException("imagecopy() returned false");
		}
		
		/**
		 * Returns the canvas object
		 * 
		 * The Canvas object can be used to draw text and shapes on the image
		 * 
		 * Examples:
		 * <code>
		 * $img = WideImage::load('pic.jpg);
		 * $canvas = $img->getCanvas();
		 * $canvas->useFont('arial.ttf', 15, $img->allocateColor(200, 220, 255));
		 * $canvas->writeText(10, 50, "Hello world!");
		 * 
		 * $canvas->filledRectangle(10, 10, 80, 40, $img->allocateColor(255, 127, 255));
		 * $canvas->line(60, 80, 30, 100, $img->allocateColor(255, 0, 0));
		 * $img->saveToFile('new.png');
		 * </code>
		 * 
		 * @return WideImage_Canvas The Canvas object
		 **/
		function getCanvas()
		{
			if ($this->canvas == null)
				$this->canvas = new WideImage_Canvas($this);
			return $this->canvas;
		}
		
		/**
		 * Returns true if the image is true-color, false otherwise
		 * 
		 * @return bool
		 **/
		abstract function isTrueColor();
		
		/**
		 * Returns a true-color copy of the image
		 * 
		 * @return WideImage_TrueColorImage
		 **/
		abstract function asTrueColor();
		
		/**
		 * Returns a palette copy (8bit) of the image
		 *
		 * @param int $nColors Number of colors in the resulting image, more than 0, less or equal to 255
		 * @param bool $dither Use dithering or not
		 * @param bool $matchPalette Set to true to use imagecolormatch() to match the resulting palette more closely to the original image 
		 * @return WideImage_Image
		 **/
		abstract function asPalette($nColors = 255, $dither = null, $matchPalette = true);
		
		/**
		 * Retrieve an image with selected channels
		 * 
		 * Examples:
		 * <code>
		 * $channels = $img->getChannels('red', 'blue');
		 * $channels = $img->getChannels('alpha', 'green');
		 * $channels = $img->getChannels(array('green', 'blue'));
		 * </code>
		 * 
		 * @return WideImage_Image
		 **/
		abstract function getChannels();
		
		/**
		 * Returns an image without an alpha channel
		 * 
		 * @return WideImage_Image
		 **/
		abstract function copyNoAlpha();
		
		/**
		 * Returns an array of serializable protected variables. Called automatically upon serialize().
		 * 
		 * @return array
		 */
		function __sleep()
		{
			$this->sdata = $this->asString('png');
			return array('sdata', 'handleReleased');
		}
		
		/**
		 * Restores an image from serialization. Called automatically upon unserialize().
		 */
		function __wakeup()
		{
			$temp_image = WideImage::loadFromString($this->sdata);
			$temp_image->releaseHandle();
			$this->handle = $temp_image->handle;
			$temp_image = null;
			$this->sdata = null;
		}
	}
