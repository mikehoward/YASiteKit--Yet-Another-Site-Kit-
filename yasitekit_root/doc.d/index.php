<html>
  <head>
    <title>YASiteKit Documentation</title>
    <link rel="stylesheet" href="/css/screen.css" type="text/css" media="screen" charset="utf-8">
    <link rel="stylesheet" href="./css/screen.css" type="text/css" media="screen" charset="utf-8">
    <link rel="stylesheet" href="/css/print.css" type="text/css" media="print" charset="utf-8">
  </head>
  <body>
<?php
function filter_func($fname)
{
  return $fname[0] != '.';
} // end of 'filter_func'()

function find_title_ar($fname)
{
  static $cwd = NULL;
  if (!$cwd) $cwd = getcwd();

  $path = $cwd . DIRECTORY_SEPARATOR . $fname;
  $f = fopen($path, 'r');
  $title_ar = array(basename($fname), '');
  while (($line = fgets($f)) !== FALSE) {
    // textile substitues the entity &#8211; for a simple hyphen (-), so we look for both
    if (preg_match('/<h1[^>]*>(.+?)\s+(.*)<\/h1>/i', $line, $match_obj)) {
      // var_dump(array_map(htmlentities, $match_obj));
      $title_ar = array(trim($match_obj[1]), ' ' . trim($match_obj[2]));
      break;
    }
  }
  fclose($f);
  return $title_ar;
} // end of find_title_ar()
?>
<div id="header">
  <a href="/index.php" class="float-left" title="YASiteKit Home" style="background:transparent;">
    <img src="/img/YASiteKitLogo.png" alt="YASiteKit.org Home" class="float-left" style="background-color:#fffff4">
  </a>
  <img src="../img/ReadDoc.png" alt="YASiteKit Doc" class="float-left">
  <h1 class="center">YASiteKit Documentation</h1>
</div>
<div id="content" class="clear-both box">
  <ul>
  <?php
    $ar = array();
    $ar = array_filter(scandir('.'), 'filter_func');
    // foreach (scandir('.') as $fname) {
    //  if ($fname[0] == '.') continue;
    //  $ar[] = $fname;
    // }
    // doc in the top level directory
    natcasesort($ar);
    foreach ($ar as $fname) {
      if (is_file($fname) && $fname != 'index.php') {
        list($link_title, $annotation) = find_title_ar($fname);
        echo "<li><a href=\"$fname\">$link_title</a>$annotation</li>\n";
      }
    }
    echo "</ul>\n";

    // doc in directories
    echo "<ul>\n";
    foreach ($ar as $fname) {
      if (is_dir($fname)) {
        $flag = TRUE;
        $ar2 = array_filter(scandir($fname), 'filter_func');
        natcasesort($ar2);
        foreach ($ar2 as $fname2) {
          if (!preg_match('/.html$/', $fname2)) {
            continue;
          }
          if ($flag) {
            echo "<li><span class=\"bold\">Directory $fname:</span>\n <ul>\n";
            $flag = FALSE;
          }
          $relative_path = $fname . DIRECTORY_SEPARATOR . $fname2;
          list($link_title, $annotation) = find_title_ar($relative_path);
          echo "  <li><a href=\"$relative_path\">$link_title</a>$annotation</li>\n";
        }
        if (!$flag) {
          echo " </ul>\n</li>\n";
        }
      }
    }
  ?>
  </ul>
</div>
<div id="footer">
  <img src="../img/PoweredBy.png" alt="Powered by YASiteKit" class="float-right">
  <ul class="center">
    
  </ul>
  
</div>
  </body>
</html>
