<?php
  // (c) Copyright 2010 Mike Howard. All Rights Reserved. 
  require_once('Product.php');
  
  function select_helper($title, $ar)
  {
    $spacer = '    ';

    array_unshift($ar, '<ul class="level-2">');
    $ar[] = "</ul>";

    return "$spacer<li class=\"level-1\">$title\n$spacer" . implode("\n$spacer", $ar) . "\n$spacer</li>\n";
   } // end of select_helper()

  function select_products()
  {
    $delta = 20;
    $tmp = Globals::$dbaccess->select_from_table('product', 'count(*) as cnt');
    $total_products = intval($tmp[0]['cnt']);
    if ($total_products <= $delta) {
      return "<li><a href=\"/DisplayProduct.php?product_menu_mode=products\">by Product</a></li>";
    }

    $ar = array();
    for ($i=1;$i < $total_products; $i += $delta) {
      $e = $i + $delta - 1;
      if ($e > $total_products) $e = $total_products;
      $ar[] = "<li><a href=\"/DisplayProduct.php?product_menu_mode=products&set_start_offset=$i&set_end_offset=$e\">products $i thru $e</a></li>";
    }
    return select_helper('by Product', $ar);
  } // end of select_products()
  
  function popular()
  {
    return "<li><a href=\"/DisplayProduct.php?product_menu_mode=popular\">All Time Popular</a></li>";
  } // end of popular()
  
  function recently_popular()
  {
    return "<li><a href=\"/DisplayProduct.php?product_menu_mode=recent-popular\">Recently Popular</a></li>";
  }
  
  function favorites()
  {
    return "<li><a href=\"/DisplayProduct.php?product_menu_mode=favorites\">Your Favorites</a></li>";
  } // end of favorites()
  
  function recent_views()
  {
    return "<li><a href=\"/DisplayProduct.php?product_menu_mode=recently-viewed\">Your Most Recent Views</a></li>";
  }

?>
<div id="main-nav">
  <ul>
    <li id="main-nav-home" class="box"><a href="/article/home">Home</a></li>
    <li id="main-nav-info" class="box"><span><a href="/article/about" title="About">About YASiteKit</a></span></li>
    <!-- <li id="main-nav-view-by" class="box"><span class="hover">Select By . . .</span>
      <ul>
      <?php
        echo select_products();
        echo popular();
        echo recently_popular();
       ?>
      </ul>
    </li> -->
    <li id="main-nav-subscribe" class="box"><span><a href="/NewsletterSubscribe.php">Get Our Newsletter</a></span></li>
    <li id="main-nav-help" class="box level-1"><span>Help</span>
      <ul class="level-2">
<?php
  require_once('Article.php');
  require_once('ArticleGroup.php');
  $article_group = new ArticleGroup(Globals::$dbaccess, 'help');
  $help_objects = $article_group->articles();
  foreach ($help_objects as $help_article):
?>
        <li><a href="/article/{:$help_article->name:}">{:$help_article->title:}</a></li>
<?php endforeach; ?>
      </ul>
    </li>
  </ul>
</div>
