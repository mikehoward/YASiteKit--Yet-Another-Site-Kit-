{:php-setup:}
$page_title = Globals::$site_name . ' - Page Not Found';
$page_header = 'Page Not Found Error';
$not_found_page = isset(Globals::$rc->safe_get_not_found_page) ? Globals::$rc->safe_get_not_found_page : 'unknown';
$body = 'Page Not Found Error:'
  . (array_key_exists('HTTP_REFERER', $_SERVER) ? " Referred from: " . $_SERVER['HTTP_REFERER'] : '')
  . " attempt to load $not_found_page failed";
{:end-php-setup:}
<?php IncludeUtilities::report_bad_thing("Page $not_found_page Not Found", $body); ?>
<h1>Error: 404 - Page Not Found</h1>
<p>Unable to load page  '{:$not_found_page:}'.</p>
