<?php

require_once('ObjectInfo.php');
require_once('RequestRouter.php');

$management_data = array(
  // Account.php
  'Account' => array(
    'routing_key' => 'manage_account',
    'resource_name' => 'Article Management',
    'script_name' => 'ManageAccount.tpl',
    'path_map' => 'userid',
    'required_authority' => 'C,A,M,V,S,X',
  ),
  
  // Article.php
  'Article' => array(
    'routing_key' => 'manage_article',
    'resource_name' => 'Article Management',
    'script_name' => 'ManageArticle.tpl',
    'path_map' => 'name',
    'required_authority' => 'A,M,V,S,X',
  ),
  // ArticleGroup.php
  'ArticleGroup' => array(
    'routing_key' => 'manage_article_group',
    'resource_name' => 'Article Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // Category.php
  'Category' => array(
    'routing_key' => 'manage_category',
    'resource_name' => 'Category Management',
    'script_name' => 'ManageCategory.tpl',
    'path_map' => 'path',
    'required_authority' => 'S,X',
  ),
  // CookieTrack.php
  'CookieTrack' => array(
    'routing_key' => 'manage_cookie_track',
    'resource_name' => 'CookieTrack Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // Email.php
  'Email' => array(
    'routing_key' => 'manage_email',
    'resource_name' => 'Email Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // ImageMagickObject.php
  'ImageMagickObject' => array(),
  // ImageObject.php
  'ImageObject' => array(),
  // Map.php
  'Map' => array(),
  // Message.php
  'Message' => array(
    'routing_key' => 'manage_message',
    'resource_name' => 'Message Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // ObjectInfo.php
  'ObjectInfo' => array(
    'routing_key' => 'manage_object_info',
    'resource_name' => 'ObjectInfo Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'X',
  ),
  // Package.php
  'Package' => array(
    'routing_key' => 'manage_package',
    'resource_name' => 'Package Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // Page.php
  // PageBase.php
  // PageSeg.php
  // PageView.php
  // PageYASiteKit.php
  // Parameters.php
  'Parameters' => array(
    'routing_key' => 'manage_parameters',
    'resource_name' => 'Article Management',
    'script_name' => 'ManageParameters.tpl',
    'path_map' => '',
    'required_authority' => 'X',
  ),
  // ReCaptcha.php
  'ReCaptcha' => array(),
  // RequestRouter.php
  'RequestRouter' => array(
    'routing_key' => 'manage_request_router',
    'resource_name' => 'RequestRouter Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'X',
  ),
  // StateMgt.php
  'StateMgt' => array(),
  // VersionObj.php
  'VersionObj' => array(
    'routing_key' => 'manage_version_obj',
    'resource_name' => 'VersionObj Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'X',
  ),
  // YATheme.php
  'YATheme' => array(
    'routing_key' => 'manage_yatheme',
    'resource_name' => 'YATheme Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // YAThemeFiles.php
  'YAThemeFiles' => array(
    'routing_key' => 'manage_yatheme_files',
    'resource_name' => 'YAThemeFiles Management',
    'script_name' => 'ManageObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
);

foreach ($management_data as $obj => $ar) {
  if (isset($ar['routing_key'])) {
    $tmp = new RequestRouter(Globals::$dbaccess, $ar);
    $tmp->save();
  }
  $tmp = new ObjectInfo(Globals::$dbaccess, $obj);
  if (isset($ar['script_name'])) {
    $tmp->manageable = 'Y';
    $tmp->management_url = $ar['script_name'];
   }
  $tmp->save();
}

$display_data = array(
  // Account.php
  // 'Account' => array(
  //   'routing_key' => 'account_display',
  //   'resource_name' => 'Article Display',
  //   'script_name' => 'DisplayAccount.tpl',
  //   'path_map' => 'userid',
  //   'required_authority' => 'C,A,M,V,S,X',
  // ),
  
  // Article.php
  'Article' => array(
    'routing_key' => 'article_display',
    'resource_name' => 'Article Display',
    'script_name' => 'DisplayArticle.tpl',
    'path_map' => 'name',
    'required_authority' => 'A,M,V,S,X',
  ),
  // ArticleGroup.php
  'ArticleGroup' => array(
    'routing_key' => 'display_article_group',
    'resource_name' => 'Article Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // Category.php
  'Category' => array(
    'routing_key' => 'display_category',
    'resource_name' => 'Category Display',
    'script_name' => 'DisplayCategory.tpl',
    'path_map' => 'path',
    'required_authority' => 'S,X',
  ),
  // CookieTrack.php
  'CookieTrack' => array(
    'routing_key' => 'display_cookie_track',
    'resource_name' => 'CookieTrack Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // Email.php
  'Email' => array(
    'routing_key' => 'display_email',
    'resource_name' => 'Email Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // ImageMagickObject.php
  // 'ImageMagickObject' => array(),
  // ImageObject.php
  // 'ImageObject' => array(),
  // Map.php
  // 'Map' => array(),
  // Message.php
  'Message' => array(
    'routing_key' => 'display_message',
    'resource_name' => 'Message Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // ObjectInfo.php
  'ObjectInfo' => array(
    'routing_key' => 'display_object_info',
    'resource_name' => 'ObjectInfo Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'X',
  ),
  // Package.php
  'Package' => array(
    'routing_key' => 'display_package',
    'resource_name' => 'Package Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // Page.php
  // PageBase.php
  // PageSeg.php
  // PageView.php
  // PageYASiteKit.php
  // Parameters.php
  // 'Parameters' => array(
  //   'routing_key' => 'display_parameters',
  //   'resource_name' => 'Article Display',
  //   'script_name' => 'DisplayParameters.tpl',
  //   'path_map' => '',
  //   'required_authority' => 'X',
  // ),
  // ReCaptcha.php
  'ReCaptcha' => array(),
  // RequestRouter.php
  'RequestRouter' => array(
    'routing_key' => 'display_request_router',
    'resource_name' => 'RequestRouter Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'X',
  ),
  // StateMgt.php
  'StateMgt' => array(),
  // VersionObj.php
  // 'VersionObj' => array(
  //   'routing_key' => 'display_version_obj',
  //   'resource_name' => 'VersionObj Display',
  //   'script_name' => 'DisplayObject.tpl',
  //   'path_map' => 'key_value',
  //   'required_authority' => 'X',
  // ),
  // YATheme.php
  'YATheme' => array(
    'routing_key' => 'display_yatheme',
    'resource_name' => 'YATheme Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
  // YAThemeFiles.php
  'YAThemeFiles' => array(
    'routing_key' => 'display_yatheme_files',
    'resource_name' => 'YAThemeFiles Display',
    'script_name' => 'DisplayObject.tpl',
    'path_map' => 'key_value',
    'required_authority' => 'S,X',
  ),
);

foreach ($display_data as $obj => $ar) {
  if (isset($ar['routing_key'])) {
    $tmp = new RequestRouter(Globals::$dbaccess, $ar);
    $tmp->save();
  }
}

