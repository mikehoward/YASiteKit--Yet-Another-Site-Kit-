<?php
echo "RequestRouter.php intialization\n";

require_once('RequestRouter.php');

foreach (array(
      array(
      'routing_key' => 'article', 
      'resource_name' => 'Articles',
      'script_name' => 'DisplayArticle.tpl',
      'path_map' => 'article',
      'required_authority' => 'ANY',
      'authority_field_name' => '',
      ),
      array(
        'routing_key' => 'manage',
        'resource_name' => 'Objects',
        'script_name' => 'ManageObject.tpl',
        'path_map' => 'object',
        'required_authority' => 'X',
        'authority_field_name' => '',
      ),
    ) as $attributes) {
  if (!AnInstance::existsP('RequestRouter', Globals::$dbaccess, $attributes['routing_key'])) {
    $obj = new RequestRouter(Globals::$dbaccess, $attributes);
    echo $obj->dump($obj->save() ? "saved\n" : "save failed\n");
  }
}
