<?php
/*
#doc-start
h1.  test_ImageObject

Created by  on 2008-08-26.
Copyright (c) 2008 Clove Technologies, Inc. All rights reserved.

#end-doc
*/


set_include_path(get_include_path() . PATH_SEPARATOR . "../system/objects" . PATH_SEPARATOR . "..");
require_once('config.php');
require_once('ImageObject.php');

//$_GET['path'] = './images/67-6x.png';
if (isset($_GET['path'])):
  extract($_GET);

$image = new ImageObject($path);
if (isset($watermark)) {
  $watermark_image = $watermark ? new ImageObject($watermark) : NULL;
  $image->displayWatermarked($watermark_image, isset($max) ? $max : 0);
} elseif (isset($merge)) {
  $merge_image = new ImageObject($merge);
  $image->displayMerged($merge_image, isset($max) ? $max : 0);
} elseif (isset($signature)) {
  $sig_image = new ImageObject($signature);
  $image->sign($sig_image);
  $image->display();
} else {
//  $image->convertToBlackAndWhite();
  $image->display(isset($max) ? $max : 0, isset($fmt) ? $fmt : 'jpg');
}
else:
?>
<div style="background:#0aa;">
<h1>Test ImageObjects</h1>

<ul>
    <li>Watermarked with generated text <img src="./test_ImageObject.php?path=./images/67-6x.png&watermark=true&max=250"></li>
  <li>Gif Display - max 150 pixels
<?php
  $image = new ImageObject('./images/67-6x.png');
  echo $image->shrink(150)->dump();
?>
    <img src="./test_ImageObject.php?path=./images/67-6x.png&max=150&fmt=gif"></li>
  <li>PNG Display - max 75 pixels
    <img src="./test_ImageObject.php?path=./images/67-6x.png&max=75&fmt=png"></li>
  <li>JPEG Display - max 101 pixels
    <img src="./test_ImageObject.php?path=./images/67-6x.png&max=101&fmt=jpg"></li>
  <li>Real Image File <img src="./images/MikeSignature.png"></li>
  <li>Raw Signature<img src="./test_ImageObject.php?path=./images/MikeSignature.png&fmt=png"></li>
  <li>Resized Signature<img src="./test_ImageObject.php?path=./images/MikeSignature.png&max=244&fmt=png"></li>
  <li>Signed image
    <img src="./test_ImageObject.php?path=./images/67-6x.png&max=400&signature=./images/MikeSignature.png "></li>
  <li><a href="./test_ImageObject.php?path=./images/67-6x.png&merge=./images/67-6x.png&debug=TRUE">See Merge Info</a></li>
  <li>Meged image - max 200 pixels - 50% reduction, centered, 50% opacity
    <img src="./test_ImageObject.php?path=./images/67-6x.png&max=200&merge=./images/67-6x.png"></li>
  <li>Watermarked image
    <img src="./test_ImageObject.php?path=./images/67-6x.png&max=200&watermark=./images/67-6x.png"></li>
    <li>Watermarked with signature file<img src="./test_ImageObject.php?path=./images/67-6x.png&watermark=./images/MikeSignature.png"></li>
</ul>
  <li>Raw Image <img src="./test_ImageObject.php?path=./images/67-6x.png" /></li>
<?php
endif;
?>
</div>
