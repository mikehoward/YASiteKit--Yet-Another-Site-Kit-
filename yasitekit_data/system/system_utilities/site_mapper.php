<?php
/*
  (c) Copyright 2010 Mike. All Rights Reserved. Licensed under GNU Lesser Public Licences, V3.
  See http://www.gnu.org/licences/lgpl.html for details
  
  This is a command line/cron-job utility which creates one or more Sitemaps and, if required,
  a Sitemap Index.
*/

// get config file
$cwd = getcwd();
chdir('..');
set_include_path('.');
require_once('config.php');
require_once('includes.php');
require_once('archive_functions.php');

chdir($cwd);
set_include_path('..' . PATH_SEPARATOR . implode(PATH_SEPARATOR, $argv) . PATH_SEPARATOR . get_include_path());

$site_map_root = Globals::$document_root;
$site_id = Globals::$site_id;
$site_url = Globals::$site_url;
$prog_name = basename(array_shift($argv));
$help =<<<EOT
Usage: $prog_name [-h | options]

Option                           Meaning
--site-map-root sitemaproot      sets directory in which to create site maps [Globals::\$document_root]
--site-id site_id                sets site_id [Globals::\$site_id]
--site-url url                   sets site_url [Globals::\$site_url]
EOT;

$drop_first = FALSE;
while (count($argv)) {
  $arg = array_shift($argv);
  switch ($arg) {
    case '-h': case '--help': echo $help; exit(0);
    case '--site-map-root': $site_map_root = array_shift($argv); break;
    case '--site-id': $site_id = array_shift($argv); break;
    case '--site-url': $site_url = array_shift($argv); break;
    default: echo $help ; exit(1);
  }
}

require_once('dbaccess.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);
$dbaccess = Globals::$dbaccess;

// run create_all_tables() non-destructively
require_once('aclass.php');
require_once('Link.php');

class SitemapMaker {
  const SITEMAP_INDEX_HEADER = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
  const SITEMAP_INDEX_TRAILER = "</sitemapindex>\n";

  const SITEMAP_HEADER = "<\x3fxml version=\"1.0\" encoding=\"UTF-8\"\x3f>
<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
      xmlns:image=\"http://www.sitemaps.org/schemas/sitemap-image/1.1\"
      xmlns:video=\"http://www.sitemaps.org/schemas/sitemap-video/1.1\">\n";
  const SITEMAP_TRAILER = "</urlset>\n";

  // timestamp
  public $starting_timestamp = NULL;

  // file names, locations, etc
  public $site_id = NULL;
  public $site_url = NULL;
  public $sitemap_root = NULL;
  
  public $sitemap_file = NULL;
  public $sitemap_fname = NULL;
  public $sitemap_len = 0;
  public $sitemap_count = 0;
  public $next_sitemap_idx = 1;
  
  public $sitemap_index_file = NULL;
  public $sitemap_index_fname = NULL;
  public $sitemap_index_len = 0;
  public $sitemap_index_count = 0;
  public $next_sitemap_index_idx = 1;

  public function __construct($site_id, $site_url, $sitemap_root) {
    $this->site_id = $site_id;
    $this->site_url = $site_url;
    $this->sitemap_root = $sitemap_root;
    if (!is_dir($sitemap_root)) {
      throw new Exeption("Sitemap Root: '$sitemap_root' is not a directory or does not exist");
    } elseif (!is_writable($sitemap_root)) {
      throw new Exeption("Sitemap Root: '$sitemap_root' is not writable");
    }
    $starting_timestamp = new DateTime();
    $this->starting_timestamp = $starting_timestamp->format('c');
    $this->open_sitemap();
  } // end of __construct()
  
  public function __get($name) {
    throw new Exception("SitemapMaker::__get(): illegal attribute name '$name'");
  } // end of __get()
  
  public function __set($name, $value) {
    throw new Exception("SitemapMaker::__set(): illegal attribute name '$name'");
  } // end of __set()
  
  public function __isset($name) {
    throw new Exception("SitemapMaker::__isset(): illegal attribute name '$name'");
  } // end of __isset()
  
  public function __unset($name) {
    throw new Exception("SitemapMaker::__unset(): illegal attribute name '$name'");
  } // end of __unset()

  public function open_sitemap() {
    $this->sitemap_fname = "sitemap-{$this->site_id}-{$this->next_sitemap_idx}.xml";
    $this->add_to_sitemap_index($this->sitemap_fname);
    
    $sitemap_path = $this->sitemap_root . DIRECTORY_SEPARATOR . $this->sitemap_fname;
    $this->next_sitemap_idx += 1;
    $this->sitemap_file = fopen($sitemap_path, "w");
    fwrite($this->sitemap_file, SitemapMaker::SITEMAP_HEADER);
    $this->sitemap_len = strlen(SitemapMaker::SITEMAP_HEADER);
    $this->sitemap_count = 0;
  } // end of open_sitemap()
  
  public function close_sitemap() {
    fwrite($this->sitemap_file, SitemapMaker::SITEMAP_TRAILER);
    fclose($this->sitemap_file);
    $this->sitemap_file = NULL;
  } // end of close_sitemap()
  
  public function write_to_sitemap($str) {
    if (!$this->sitemap_file) {
      $this->open_sitemap();
    }
    $this->sitemap_len += strlen($str);
    $this->sitemap_count += 1;
    if ($this->sitemap_len > 10485700 || $this->sitemap_count >= 50000) {
      $this->close_sitemap();
      $this->open_sitemap();
      $this->sitemap_len += strlen($str);
      $this->sitemap_count += 1;
    }
    fwrite($this->sitemap_file, $str);
  } // end of write_to_sitemap()

  public function open_sitemap_index() {
    $this->sitemap_index_fname = "sitemap_index-{$this->site_id}-{$this->next_sitemap_index_idx}.xml";
    $sitemap_index_path = $this->sitemap_root . DIRECTORY_SEPARATOR . $this->sitemap_index_fname;
    $this->next_sitemap_index_idx += 1;
    $this->sitemap_index_file = fopen($sitemap_index_path, "w");
    fwrite($this->sitemap_index_file, SitemapMaker::SITEMAP_INDEX_HEADER);
    $this->sitemap_index_len = strlen(SitemapMaker::SITEMAP_INDEX_HEADER);
    $this->sitemap_index_count = 0;
  } // end of open_sitemap_index()
  
  public function close_sitemap_index() {
    fwrite($this->sitemap_index_file, SitemapMaker::SITEMAP_INDEX_TRAILER);
    fclose($this->sitemap_index_file);
    $this->sitemap_index_file = NULL;
  } // end of close_sitemap_index()
  
  public function add_to_sitemap_index($sitemap) {
    if (!$this->sitemap_index_file) {
      $this->open_sitemap_index();
    }
    $str = "  <sitemap>\n";
    $str .= "    <loc>{$this->site_url}/{$sitemap}</loc>\n";
    $str .= "    <lastmod>{$this->starting_timestamp}</lastmod>\n";
    $str .= "  </sitemap>\n";

    $this->sitemap_index_len += strlen($str);
    $this->sitemap_index_count += 1;
    if ($this->sitemap_index_len < 10485700 && $this->sitemap_index_count < 50000) {
      fwrite($this->sitemap_index_file, $str);
    } else {
      $this->close_sitemap_index();
      $this->open_sitemap_index();
      $this->sitemap_index_len += strlen($str);
      $this->sitemap_index_count += 1;
      $this->add_to_sitemap_index($sitemap);
    }
  } // end of add_to_sitemap_index()
}


$obj = new Link($dbaccess);
$list = $obj->get_objects_where(array('follow' => 'Y'));

$sitemap_maker = new SitemapMaker($site_id, $site_url, $site_map_root);

foreach ($list as $link) {
  $sitemap_maker->write_to_sitemap($link->site_map());
}
$sitemap_maker->close_sitemap();
$sitemap_maker->close_sitemap_index();
