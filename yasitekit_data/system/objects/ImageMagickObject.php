<?php
/*
#doc-start
h1.  ImageMagickObject - a Utility object for manipulating and displaying ImageMagicks using GD2

Class ImageMagickObject manages in-memory image data. ImageMagicks in ImageMagickObjects are automatically
destroyed by the destructor when the objects are cleaned up. This is nice because
it means you never have to call _imagedestroy()_.

Created by  on 2008-09-09.
Copyright (c) 2008 Clove Technologies, Inc. All rights reserved.

h1. DOESN'T WORK YET - NOT EVEN STARTED - DON'T USE YET!!!!!!!

* new ImageMagickObject(path OR width, height) - creates a new ImageMagickObject object from
the file located by the _path_ OR creates a new empty ImageMagick of size (width, height)

h3. Attributes

* width - image width
* height - image height
* max - max of width and height
* mime - mime type as in 'image/jpeg' - May be NULL
* image - reference to real, live image object. Don't destroy it, but you
can call various image...() functions if you have to. It's a good idea to
defer to the defined methods because some of them do some extra bookkeeping -
for example _allocatecolor()_ maintains a dictionary of colors allocated for
the image and checks for and avoids duplicate color allocataion.
* path - path to this image - May be NULL

h3. Instance Methods

* save(path, format = GD2) - Refuses
to clobber the original path. Throws exceptions on problems.
* convertToBlackAndWhite(threshold) - rewrites all colors as black or white pixels. Perserves
alpha. NOTE: this is a destructive transformation and is SLOW
* shrink(max-dimension) - returns an ImageMagickObject with width and height
no greater than max-dimension.
* display(max-dimension = 0, output_format = IMAGETYPE_PNG) - outputs image to web browser.
_max_dimension_ == 0 displays at full size. Output format can be a numeric GD constant
or one of 'jpg', 'jpeg', 'gif', 'png', 'wbmp' or 'bmp'
* merge(overlay_image, max_dimension, opacity = 100, ratio = 0, center_x = .5, center_y = .5) -
returns the merged image.
_opacity_ is the opacity value - 0 to 100.
_ratio_ is overlay image size in percent relative to the resulting image. If 0, then the
overlay is not rescaled other than to keep it within the size of the image.
_center_x_ and _center_y_
are the 0 to 1.0 fractions used to position the center point of the overlay relative to the
image. Returns a new ImageMagickObject
* overlay(overlay-image, max_dimension = 0, ratio = 100%, center_x = 0.5, center_y = 0.5) -
does an overlay of the overlay image using _imagecopy()_. NOTE: there is no _opacity_
parameter.Returns a new ImageMagickObject.
* displayWatermarked(watermark-imageObject = NULL, max_dimension = 0, output_format = IMAGETYPE_PNG) - outputs
this image with the _watermark_ image reduced 50% and centerd at 30% opacity.
NONDESTRUCTIVE

h3. watermark(watermark_image = NULL, max_dimention = 0)
  
* watermark(watermark_image = NULL, max_dimention = 0) - watermarks this image
with either a dynamically generated string or a supplied ImageMagickObject. The default
is dynamically generated string.
* displayMerged(watermark_image, max_dimension = 0, ratio = 100, opacity = 50, output_format = PNG) - merges _merg_file_ into this image with specified ratio and opacity. Output goes
to browser. NONDESTRUCTIVE
* displaySigned(signature-image, max-dimension = 0, output-format = PNG) - merges
the signature file - converted to black and transparent - at 10% size and 100%
opacity into the bottom right hand corner of the image. NONDESTRUCTIVE
 Destructively converts signature image to black and transparent.
 * sign(signature-image, max-dimension = 0, ratio = 10, opacity = 100, center_x = 1.0,
 center_y = 1.0) - signs the image. Destructively converts signature image to black and
 transparent.

h3. Instance Methods

* emptyP() - returns TRUE if image is empty, else FALSE

h3. Protected Methods

* protected rgbtocolor(red, green, blue, alpha = 0) - returns a packed 32 bit word
containing the colors and alpha shifted into 8 bit positions (alpha, red, green, blue).
Goes between truecolor and palatte
* protected colortorgba(color) - returns an array of 8 bit color values and alpha
as array(red, green, blue, alpha). [Alpha is clipped to 7 bits]
* protected allocatecolor(name, red, green, blue, alpha = 0) - allocates a color
for _this->image_ and caches the image resource under _name_. This creates a central
resevour of named colors so we don't get duplication in the image. The allocated
color handles are available in the associated array _this->color_allocations['name']
* protected set_transparent(color_index = NULL) - if _color_index_ is _not_ NULL,
sets the _transparent_ color for _this->image_ to the indicated color and RETURNs
the transparent color resource for _this->image_; If NULL, just
returns the transparent color resources for _this->image_.
* protected fill(color_index) - Fills the entire image with a single color
* protected overwriteThis($source_image) - overwrites all of the attributes of _this_
with the attributes of the _source_image_. Used as final step after
manipulating an image using various imagecopy...() functions.
* protected getInfoArray(path) - returns the merged array from the GD2 function _getimagesize()
or FALSE if the file is not readable or is not a file. *NOTE:* 
width and height may be zero - which means the underlieing GD library couldn't figure
them out.
Info is under the following keys:
** width - width of image in pixels
** height - height of image in pixels
** image_type - defined constant from IMAGETYPE_XXX. values for JPG, PNG, and GIF are 2, 3,
and something else
** image_ext - defined for jpg, png and gif's only and gives the common three letter extension
for those type files
** img_attribute - gives the width and height attributes for an &lt;img&gt; tag.
** bits - may be defined and specifies number of bits of color information
** mime - is the MIME type for an HTTP header
** APP13 - if defined - _may_ be the IPTC data for the image
** APP?? - if defined - are other things which might be of interest, if you can find
out what they are and how to decode them.
* protected getImageMagick() - loads the image into memory from the given path

#doc-end
*/

class ImageMagickObjectException extends Exception {}

class ImageMagickObject {
  protected $path = NULL;
  protected $image = NULL;
  protected $image_info = NULL;
  protected $color_allocations = array();
  protected $transparent_color = NULL;

  public function __construct()
  {
    $args = func_get_args();
    if (count($args) == 2 && is_int($args[0]) && is_int($args[1])) {
      list($width, $height) = $args;
      $this->image_info = array('width' => $width, 'height' => $height);
      $this->image = imagecreatetruecolor($width, $height);
    } elseif (count($args) && is_string($args[0])) {
      $this->path = $args[0];
      if ($this->image_info = $this->getInfoArray($this->path)) {
       $this->getImageMagick($this->path);
      }
    } else {
      throw new ImageMagickObjectException("ImageMagickObject::__construct("
        . implode(',', $args) . "): bad arguments - must be path or width,height");
    }
  }
  
  public function __destroy()
  {
    if ($this->image) {
      imagedestroy($this->image);
    }
  }
  
  public function __get($name)
  {
    if (!$this->image_info) {
      return FALSE;
    }
    if (in_array($name, array('width', 'height', 'mime', 'path'))) {
      return isset($this->image_info[$name]) ? $this->image_info[$name] : NULL;
    } elseif ($name == 'max') {
      return max($this->image_info['width'], $this->image_info['height']);
    } elseif ($name == 'image') {
      return $this->image;
    } elseif ($name == 'path') {
      return $this->path;
    } else {
      return FALSE;
    }
  }
  
  public function __set($name, $value)
  {
    return FALSE;
  }
  
  public function __isset($name)
  {
    if (!$this->image_info) {
      return FALSE;
    } else {
      return in_array($name, array('width', 'height', 'mime', 'image', 'max', 'path'));
    }
  }

  public function __unset($name)
  {
    throw new ImageMagickObjectException("ImageMagickObject::__unset($name) - illegal action");
  }
  
  public function dump($msg = NULL)
  {
    ob_start();
    echo "<pre>";
    if ($msg) {
      echo "$msg\n";
    }
    echo "image_info: \n";
    var_dump($this->image_info);
    foreach (array('width', 'height', 'mime', 'path', 'max') as $attr) {
      echo " $attr: {$this->$attr}\n";
    }
    echo "</pre>";
    return ob_get_clean();
  }

  protected static function rgbtocolor($r, $g, $b, $alpha = 0)
  {
    return (($alpha & 0x7f)<<24) | (($r & 0x0ff)<<16) | (($g & 0x0ff)<<8) | ($b & 0x0ff);
  }

  protected static function colortorgba($color)
  {
    return array(($color>>16) & 0x0ff, ($color>>8) & 0x0ff, $color & 0x0ff, (($color>>24) & 0x7f));
  }

  protected function allocatecolor($name, $red, $green, $blue, $alpha = 0)
  {
    if (!$this->image) {
      return;
    }
    if (!array_key_exists($name, $this->color_allocations)) {
      $this->color_allocations[ImageMagickObject::rgbtocolor($red, $green, $blue)] =
        $this->color_allocations[$name] =
          imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha);
    }
    return $this->color_allocations[$name];
  }


  protected function set_transparent($color_index = NULL)
  {
    if (!$this->image) {
      return;
    }
    if ($color_index) {
      $this->transparent = $color_index;
      return imagecolortransparent($this->image, $this->color_allocations[$color_index]);
    } else {
      return imagecolortransparent($this->image);
    }
  }

  protected function fill($color_index)
  {
    if (!$this->image) {
      return;
    }
    return imagefill($this->image, 0, 0, $this->color_allocations[$color_index]);
  }
  
  protected function overwriteThis($source_image)
  {
    foreach (array('width', 'height', 'path', 'image', 'image_info',
      'color_allocations', 'transparent_color') as $name) {
        $this->$name = $source_image->$name;
    }
//    foreach (array('width', 'height', 'path', 'image', 'image_info',
//      'color_allocations', 'transparent_color') as $name) {
//        echo $this->$name == $source_image->$name . "\n";
//    }
  }

  protected function getInfoArray($path)
    {
      static $imagetype_to_extension = array( IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg',
          IMAGETYPE_GIF => 'gif');

      if (!is_file($path) || !is_readable($path)) {
        return FALSE;
      }

      if (!($ar = getimagesize($path, $image_info))) {
        return FALSE;
      }
      foreach (array('width' => 0, 'height' => 1, 'image_type' => 2, 'img_attribute' => 3)
        as $key => $val) {
          $ar[$key] = $ar[$val];
          unset($ar[$val]);
      }
      if (isset($imagetype_to_extension[$ar['image_type']])) {
        $ar['image_ext'] = $imagetype_to_extension[$ar['image_type']];
      }

      return array_merge($image_info, $ar);
    }

    // returns a gd2 true color image object given the path to a file or FALSE;
    protected function getImageMagick()
    {
      // extract image information
      extract($this->image_info);
      // get source image depending on type
      switch ($image_type) {
        case IMAGETYPE_JPEG:
        $source_image = imagecreatefromjpeg($this->path);
        break;
        case IMAGETYPE_PNG:
        $source_image = imagecreatefrompng($this->path);
        break;
        case IMAGETYPE_GIF:
        $source_image = imagecreatefromgif($this->path);
        break;
        case IMAGETYPE_GD:
        $source_image = imagecreatefromgd($this->path);
        break;
        case IMAGETYPE_GD2:
        $source_image = imagecreatefromgd2($this->path);
        break;
        default:
        return FALSE;
      }

      if ($width == 0 && ($width = imagex($source_image)) == 0) {
        imagedestroy($source_image);
        return FALSE;
      }
      if ($height == 0 && ($height = imagey($source_image)) == 0) {
        imagedestroy($source_image);
        return FALSE;
      }

      // copy to truecolor image
      // This appears to be necessary in order to handle images with transparent backgrounds
      //   at least that's what happened with my signature image
      $this->image = imagecreatetruecolor($width, $height);
      $gray = $this->allocatecolor('gray', 127, 127, 127, 0);
      imagealphablending($this->image, TRUE);
      $this->set_transparent($gray);
      $this->fill($gray);
  //    imagealphablending($source_image, TRUE);
  //   imagecopymerged makes the entire image of a transparent background black
  //    imagecopymerge($this->image, $source_image, 0, 0, 0, 0, $width, $height, 100);
      imagecopy($this->image, $source_image, 0, 0, 0, 0, $width, $height);
      imagedestroy($source_image);

      return TRUE;
    }

    // sendToBrowser(image, output_format) puts all the image output handling in a single place
    protected function sendToBrowser($output_format = IMAGETYPE_PNG)
    {
      switch ($output_format) {
        case 'jpeg': case 'jpg':
        case IMAGETYPE_JPEG: header('Content-Type: image/jpeg'); imagejpeg($this->image); break;
        case 'png':
        case IMAGETYPE_PNG: header('Content-Type: image/png'); imagepng($this->image); break;
        case 'gif':
        case IMAGETYPE_GIF: header('Content-Type: image/gif'); imagegif($this->image); break;
        case 'wbmp': case 'bmp':
        case IMAGETYPE_WBMP: header('Content-Type: image/wbmp'); imagewbmp($this->image); break;
        default: break;
      }
    }

  // Public Methods
  public function emptyP()
  {
    return $this->image == NULL;
  }

  public function save($save_path, $format = 'gd2')
  {
    if (!$this->image) {
      return FALSE;
    }
    if ($save_path == $this->path) {
      throw new ImageMagickObjectException("ImageMagickObject::save($save_path) - save path collision");
    }
    $dir_path = dirname($save_path);
    if (!is_dir($dir_path)) {
      throw new ImageMagickObjectException("ImageMagickObject::save($save_path,...): directory '$dir_path' does not exist");
    }
    if (!is_writable($dir_path)) {
      throw new ImageMagickObjectException("ImageMagickObject::save($save_path,...): directory '$dir_path' not writeable");
    }
    switch ($format) {
      case 'jpeg': case 'jpg':
      case IMAGETYPE_JPEG:
      imagejpeg($this->image, $save_path);
      break;
      case 'png':
      case IMAGETYPE_PNG:
      imagepng($this->image, $save_path);
      break;
      case 'gif':
      case IMAGETYPE_GIF:
      imagegif($this->image, $save_path);
      break;
      case 'gd':
      case IMAGETYPE_GD:
      imagegd($this->image, $save_path);
      break;
      case 'gd2':
      case IMAGETYPE_GD2:
      imagegd2($this->image, $save_path);
      break;
      default:
      throw new ImageMagickObjectException("ImageMagickObject::save($save_path, $format, ...) - unsupported format");
    }
    return TRUE;
  }

  public function convertToBlackAndWhite($threshold = 127)
  {
    extract($this->image_info);
    $this->allocatecolor('black', 0, 0, 0, 0);
    $this->allocatecolor('white', 255,255,255,0);
//    $this->transparent(-1);
    $black = 0;
    $white = ImageMagickObject::rgbtocolor(255,255,255);
    for ($x=0;$x<$width;$x++) {
      for ($y=0;$y<$height;$y++) {
        $colorat = imagecolorat($this->image, $x, $y);
        $alpha = $colorat & 0x7f000000;
        $avg_color = ((($colorat >> 16) & 0x0ff) + (($colorat >> 8) & 0x0ff) + ($colorat & 0x0ff)) / 2.0;
        $newcolor = ($avg_color > $threshold ? $white : $black) | $alpha;
        imagesetpixel($this->image, $x, $y, $newcolor);
      }
    }
  }

  public function convertToBlackAndTransparent($threshold = 127)
  {
    $this->convertToBlackAndWhite($threshold);
    $white = $this->allocatecolor('white', 255,255,255);
    $this->set_transparent($white);
  }

  public function shrink($max_dimension)
  {
    extract($this->image_info);
    $max = $this->max;
    $fraction = ($max_dimension < 1) ? 1.0 : ($max_dimension < $max ? $max_dimension / $max : 1.0);

    $new_width = intval($fraction*$width + 0.5);
    $new_height = intval($fraction*$height + 0.5);
    $new_image = new ImageMagickObject($new_width, $new_height);
    $gray = $new_image->allocatecolor('gray',127,127,127,0);
    $new_image->set_transparent($gray);
    $new_image->fill($gray);
//    imagealphablending($new_image, TRUE);
// imagecopyresampled() introduces vertical bars (sometimes)
//      imagecopyresampled($new_image->image, $this->image, 0, 0, 0, 0,
    imagecopyresized($new_image->image, $this->image, 0, 0, 0, 0,
        $new_width, $new_height, $width, $height);

    return $new_image;
  }

  public function display($max_dimension = 0, $output_format = IMAGETYPE_PNG)
  {
    if ($this->emptyP()) {
      throw new ImageMagickObjectException("ImageMagickObject::display($max_dimension, $output_format): attempt to display void image");
    }
    extract($this->image_info);
    $max = $width > $height ? $width : $height;

    if ($max_dimension <= 0.99) {
      $max_dimension = $max;
      $image = $this;
    } elseif ($max_dimension < $max) {
      $image = $this->shrink($max_dimension);
    } else {
      $image = $this;
    }
    
    $image->sendToBrowser($output_format);
  }

  public function merge($overlay, $max_dimension = 0, $opacity = 100, $ratio = 0,
    $center_x = .5, $center_y = .5)
  {
    $image = $this->shrink($max_dimension);
    $width = $image->width;
    $height = $image->height;
    $max_dimension = $image->max;
    if (is_string($overlay) && file_exists($overlay)) {
      $overlay = new ImageMagickObject($overlay);
    }
    if ($overlay instanceof ImageMagickObject) {
      if ($ratio > 0) {
        $fraction = floatval(min($ratio, 100.0)) / 100.0;
        $overlay_image = $overlay->shrink($max_dimension * $fraction);
      } else {
        $o_width = $overlay->width;
        $o_height = $overlay->height;
        if ($o_width > $width || $o_height > $height) {
           $fraction = min(floatval($width) / floatval($o_width), floatval($height) / floatval($o_height));
           $overlay_image = $overlay->shrink(max($o_width, $o_height) * $fraction);
        } else {
          $fraction = 1.0;
          $overlay_image = $overlay;
        }
      }
      $overlay_width = imagesx($overlay_image->image);
      $overlay_height = imagesy($overlay_image->image);
  
      $center_x = $center_x < 0 ? 0 : ($center_x > 1 ? 1.0 : $center_x);
      $center_y = $center_y < 0 ? 0 : ($center_y > 1 ? 1.0 : $center_y);
      $dst_x = intval(($width - $overlay_width) * $center_x + 0.5);
      $dst_y = intval(($height - $overlay_height) * $center_y + 0.5);
      $dst_w = intval($fraction * $width + 0.5);
      $dst_h = intval($fraction * $height + 0.5);
      imagealphablending($image->image, TRUE);
      $merge_status = imagecopymerge($image->image, $overlay_image->image, $dst_x, $dst_y, 0, 0,
        $overlay_width, $overlay_height, $opacity);
    }
  
    return $image;
  }

  
  public function overlay($overlay, $max_dimension = 0, $ratio = 100,
    $center_x = 0.5, $center_y = 0.5)
  {
    $image = $this->shrink($max_dimension);
    $width = imagesx($image->image);
    $height = imagesy($image->image);
    $max_dimension = $width > $height ? $width : $height;
    $fraction = $ratio / 100.0;
    $overlay_image = $overlay->shrink($max_dimension * $fraction);
    $overlay_width = imagesx($overlay_image);
    $overlay_height = imagesy($overlay_image);
  
    $center_x = $center_x < 0 ? 0 : ($center_x > 1 ? 1.0 : $center_x);
    $center_y = $center_y < 0 ? 0 : ($center_y > 1 ? 1.0 : $center_y);
    $dst_x = intval(($width - $overlay_width) * $center_x + 0.5);
    $dst_y = intval(($height - $overlay_height) * $center_y + 0.5);
    $dst_w = intval($fraction * $width + 0.5);
    $dst_h = intval($fraction * $height + 0.5);

    imagealphablending($image->image, TRUE);
    $merge_status = imagecopy($image->image, $overlay_image->image, $dst_x, $dst_y, 0, 0,
      $overlay_width, $overlay_height);

    return $image;
  }

  // checks watermark image and either draws one or creates a fake with the
  //  text "Copyrighted ImageMagick"
  private function checkWatermarkImageMagick($watermark_image)
  {
    $opacity = 30;
    if ($watermark_image instanceof ImageMagickObject && !$watermark_image->emptyP()) {
       return array($watermark_image, $opacity);
    }

    // create watermark
    // configurable parameters
    $font_number = 5;
    $font_text = "Copyrighted ImageMagick";
    $char_spacing = 2;
    // computed parameters
    $font_char_count = strlen($font_text);
    $char_height = imagefontheight($font_number);
    $char_width = imagefontwidth($font_number);
    $char_box_height = $char_height + 2 * $char_spacing;
    $char_box_width = $char_width + $char_spacing;
    // using freetype code
    // $watermark_image = new ImageMagickObject($this->width, $this->height);
    $watermark_image = new ImageMagickObject(strlen($font_text) * $char_box_width + $char_spacing,
        $char_box_height);
    $gray = $watermark_image->allocatecolor('gray',127,127,127,0);
    $watermark_image->set_transparent($gray);
    $watermark_image->fill($gray);
//    $white = $watermark_image->allocatecolor('white', 255,255,255,0);
    $green = $watermark_image->allocatecolor('green', 0,255,0, 0);
    $black = $watermark_image->allocatecolor('black', 0,0,0, 0);

    $text_x = $char_spacing;
    $text_y = $char_spacing;
    for ($idx = 0; $idx < $font_char_count ; $idx++ ) {
      imagechar($watermark_image->image, $font_number, $text_x, $text_y, $font_text[$idx], $black);
      $text_x += $char_box_width;
    }

    // using freetype code - better fonts, but . . .
    // $font_path = pathStringToPath('{images}-fonts-DAELC___.TTF');
    // $bounding_box = imageftbbox($font_size /* pts */, 0 /* angle */, $font_path /* font file */,
    //   $font_text);
    // $bb_width = $bounding_box[2] - $bounding_box[0];
    // 
    // // readjust font size
    // //echo "$font_size \n";
    // $font_size *= ($this->width / $bb_width);
    // //echo "$font_size \n";
    // $bounding_box = imageftbbox($font_size /* pts */, 0 /* angle */, $font_path /* font file */,
    //   $font_text);
    // $bb_width = $bounding_box[2] - $bounding_box[0];
    // $bb_height = $bounding_box[1] - $bounding_box[5];
    // $text_x = ($this->width - $bb_width)/2;
    // $text_y = ($this->height - $bb_height)/2;
    // //echo "$this->width, $this->height, $bb_width, $bb_height, $text_x, $text_y\n";
    // imagefttext($watermark_image->image, $font_size, 0, 0, $text_y, $green, $font_path,
    //   $font_text);
    // 
    // $opacity = 100;

    return array($watermark_image, $opacity);
  }
  
  public function displayWatermarked($watermark_image = NULL, $max_dimension = 0, 
    $output_format = IMAGETYPE_PNG)
  {
    list($watermark_image, $opacity) = $this->checkWatermarkImageMagick($watermark_image);
    $image = $this->merge($watermark_image, $max_dimension, $opacity, 50);
    $image->sendToBrowser($output_format);
  }

  public function watermark($watermark_image = NULL, $max_dimension = 0)
  {
    list($watermark_image, $opacity) = $this->checkWatermarkImageMagick($watermark_image);
    $image = $this->merge($watermark_image, $max_dimension, $opacity, 50);
    $this->overwriteThis($image);
  }

  public function displayMerged($watermark_image, $max_dimension = 0, $ratio = 100,
    $opacity = 50, $output_format = IMAGETYPE_PNG)
  {
    $image = $this->merge($watermark_image, $max_dimension, $opacity, $ratio);
    $image->sendToBrowser($output_format);
  }

  public function displaySigned($signature_image, $max_dimension = 0,
    $output_format = IMAGETYPE_PNG)
  {
    $signature_image->convertToBlackAndTransparent();
    $image = $this->merge($signature_image, $max_dimension, 100, 10, 1.0, 1.0);
    $image->sendToBrowser($output_format);
  }

  public function sign($signature_image, $max_dimension = 0, $ratio = 10, $opacity = 100,
    $center_x = 1.0, $center_y = 1.0)
  {
    $signature_image->convertToBlackAndTransparent();
    $image = $this->merge($signature_image, $max_dimension, $opacity, $ratio, $center_x, $center_y);
    $this->overwriteThis($image);
  }
}
?>
