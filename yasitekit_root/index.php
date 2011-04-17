<?php
  if (!function_exists('gethostname')) {
    function gethostname() { return php_uname('n'); }
  }
  if (preg_match('/Macintosh.*.local/', ($hostname = gethostname()))) {
    $hostname = 'localhost';
  }
  switch ($hostname) {
    case 'Macintosh.local':
    case 'localhost':
    case 'mike':
    case 'Mike.local':
    case 'mike.local':
      $private_data = dirname(getcwd()) . DIRECTORY_SEPARATOR . 'yasitekit_data';
      set_include_path($private_data . PATH_SEPARATOR . get_include_path());
      break;
    case 'qiorre.pair.com': set_include_path('/usr/home/mikehow/private_data/yasitekit'
      . PATH_SEPARATOR . get_include_path()); break;
    default: echo gethostname();echo "<h1>Sorry - don't run on this host</h1>"; exit();
  }

  require_once('config.php');
  require_once('includes.php');

  switch (Globals::$dbaccess->on_line) {
    case 'F':
      Globals::$rc->safe_get_renderer = 'render_admin_page.php';
      Globals::add_message('Site is Off Line');
      break;
    case 'T':
      break;
    case 'R':
      break;
  }
  
  // see if this a routable path
  $ar = explode('/', Globals::$page_name);
  while ($ar && is_array($ar) && !$ar[0]) {
    array_shift($ar);
  }

  require_once('RequestRouter.php');
  $routing_key = array_shift($ar);
  if (AnInstance::existsP('RequestRouter', Globals::$dbaccess, $routing_key)) {
    Globals::$router_obj = new RequestRouter(Globals::$dbaccess, $routing_key);
    try {
      Globals::$router_obj->map_pathinfo(implode('/', $ar));
      Globals::$page_name = Globals::$router_obj->script_name;
      Globals::$page_ext = preg_replace('/^.*\./', '', Globals::$router_obj->script_name);
    } catch (Exception $e) {
      IncludeUtilities::redirect_to('/page_not_found_page.tpl?not_found_page=' . Globals::$page_name
        . ": exception: $e",  basename(__FILE__) . ": " . __LINE__);
    }
  }

  if (Globals::$rc->safe_get_renderer) {
    $renderer = Globals::$rc->safe_get_renderer;
    unset(Globals::$rc->safe_get_renderer);
    require($renderer);
  } else {
    switch (Globals::$page_ext) {
      case 'php':
        if (preg_match('/^\/?index.php$/', Globals::$page_name)) {
          // // redirect to home page
          // Globals::$page_name = 'DisplayArticle.php';
          // Globals::$rc->safe_get_page_name = 'DisplayArticle.php';
          // Globals::$rc->safe_get_article = 'home';
          // Globals::$page_name = 'Home.php';
          // Globals::$rc->safe_get_page_name = 'Home.php';
          IncludeUtilities::redirect_to('/article/home', basename(__FILE__) . ": " . __LINE__);
        } else {
          require('render_page.php');          
        }
        // require('render_tpl.php');
        break;
      case 'tpl':
        require('render_tpl.php');
        break;
      default:
        Globals::$page_name = '/page_not_found_page.tpl?not_found_page=' . Globals::$page_name;
        Globals::$page_ext = 'php';
        require('render_page.php');
        break;
    }
  }
?>
