{:php-setup:}
  $page_title = Globals::$site_name . ' - Access Denied';
  $page_header = 'Access Denied';
  $error_message = Globals::$site_installation == 'development' ? Globals::$session_obj->render_messages_and_clear() : '';
{:end-php-setup:}
{:authority ANY:}
<h1>Access Denied</h1>
<p>404 error</p>
{:$error_message:}
