<?php
/*
#doc-start
h1.  render_web_service.php - returns a JSON or XML object - as requested

Created by  on 2010-03-11.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module implements a framework for several web services. Additionally, it
implements json and xml services to return the values of specified objects.

Web services are inheritly unsafe. This model addresses this by implementing
a simple security model which allows Object and Field level control over which
objects are retrievable. Specific service commands are free to add to, or ignore
this.

The bulk of common processing is bundled into a single WebService class - which
is available to service commands via the variable *Globals::$web_service*.

p(warning). %Web Services silently abort if the database is NOT online.%

h2. Web API

h3. Request URL

Web service requests are made using a normal http request with an encoded URL.

The service request defines the data protocol - such as raw JSON or RSS, the
specific service requested, and passes parameters to the service request.

The url will look like:

http://_host-name_/_data_protocol_/_service_command_?_query_string_

Where:

* _host-name_ is the name of the site. For example, www.yasitekit.org
* _data_protocol_ defines how the data will be returned. Data protocols are:
** xml - implemented - returns an XML document "see":#json_format
** json - implemented - returns a JSON document "see":#xml_format
** rss - planned - TBD
** atom - planned - TBD
* _service_command_ - this is either the distinguished name _object_ or the name
of a script in the _web_service_ directory.
* _query_string_ is a normal GET command query string which is passed to the _service_command_.
These values are available to _service commands_ via the request cleaner at *Globals::$rc*.
See "request_cleaner.php":/doc.d/system-includes/request_cleaner.html for details.

h3. The _object_ service command

The format of the returned data depends on the _data_protocol_ requested.

h4. JSON returned data

The returned object is a JSON object, created by the PHP function json_encode().
It contains the following standard fields:

* result - always returned - string - 'success' or 'failure'
* explanation - always returned - string - free text
* object_count - only returned on success - integer - number of objects which follow
* object_ar - only returned on success - array of JSON objects.

Each object in the array is a JSON encoded array of (field, value) pairs, where _field_
is the field name of a YASiteKit object and _value_ is its value.

h4. XML returned data

The returned data is well formed XML according to version 1.0 of the XML specification.

The top level element has the element name taken from *Globals::$site_tag* - typically a
lower case version of the web site name - with the 'www.' prefix stripped.

This element will contain two or four child elements:

* result - always present - which will be either 'success' or 'failure' [quote marks omitted]
* explanation - always present - which will be empty on success and human readable text on failure
* object_count - only returned on success - integer - number of objects returned
* object_ar - only returned on success. This will contain 0 or more object elements. This
element has two attributes
** count - integer - number of objects contained
** object_name - string - the class name of the objects being returnd

Object elements are well formed XML. The element tag is the case sensitive name of the
object returned. There is one attribute:

* _index_ - integer - the 0 based index of the element in the array of elements.

Each object element will contain 0 or more elements containing field data. The name of
each field element is the _field name_ as defined in the class definition. The value is
the value of that field for the returned instance of the object.

Only fields with the 'public' property set are returned.


h4. RSS returned data

TBD

h4. ATOM returned data

TBD

h3. Other Service Commands

Other service commands will return data in their own formats. See the documentation
in the _web_service_ directory for details.

h2. Security

Web services appear to be inherently insecure, so this feature adopts a simple
'controlled visibility' model. The default for all objects and fields within
objects is 'invisible'.

Each object has a parameter value in the Parameters object named 'webservice'
which is a boolean. It may take the values T or F. Absence of the parameter
OR anything which is not the literal character 'T' is equivalent to F.

Further, each field which is visible must be have the property _public_. This
property is set in object definition, is a programatic value, does not persist
in the data store, but is defined in the code.

h2. Command Interface - aka API - aka WebService Class

This module defines the WebService object. The instance reflecting this HTTP
request is assigned to the variable *Globals::$web_service* and provides
the bulk the services required for completing a request.

Service Commands MUST use the services of the WebService class as described below.



h2. WebService Class

h3. Instantiation

pre. Globals::$web_service = new WebService(_service_type_, _service_command_);

Where _service_type_ is:

* json - supported
* xml - supported
* rss - Planned
* atom - planned

h3. Attributes

h3. Class Methods

None

h3. Instance Methods

* render() - causes the object to render its content.
* obj_to_array($object) - returns an associative array with all the _attributes_ of the _$object_
as keys with the same values as in _$object_.
* array_to_xml($name, $ar) - returns an XML 1.0 string containing the array. The outermost element
will have element tag _$name_. It will contain elements with tags equal to the array keys and values
equal to their values. No elements have attributes.

#end-doc
*/

// abort if database is NOT online
if (Globals::$dbaccess->on_line != 'T') {
  return;
}

// class definition start
class WebServiceException extends Exception {}

class WebService {
  private $service_command;
  private $service_type;
  private $content = '';
  
  public function __construct($service_type, $service_command)
  {
    switch ($service_type) {
      case 'json':
      case 'xml':
        $this->service_type = $service_type;
        set_include_path(Globals::$private_data_root . DIRECTORY_SEPARATOR . $service_type . PATH_SEPARATOR
            . Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system'
              . DIRECTORY_SEPARATOR . $service_type . PATH_SEPARATOR
           . get_include_path());
        break;
      case 'rss':
      case 'atom':
        throw new WebServiceException("WebService::__construct($service_type, ...): Service type '$service_type' not yet implemented");
      default:
        throw new WebServiceException("WebService::__construct($service_type, ...): Illegal Web Service Request");
    }

    if ($service_command == 'object') {
      if (!Globals::$rc->safe_get_object) {
        // $this->content = array('result' => )
      }
      
    } else {
      ob_start();
      $include_result = include($service_command);
      $this->content = ob_get_clean();
      // file_put_contents('/tmp/obj-to-json.out', $this->content);
      if ($include_result === FALSE) {
        $this->content = "unable to include $service_command: " . Globals::$rc->dump();
      }
    }
  } // end of __construct()
  
  public function __set($name, $value)
  {
    switch ($name) {
      case 'content':
        $this->content = $value;
        break;
      default:
        throw new WebServiceException("WebService::__set($name, value): Illegal attribute name '$name'");
    }
  } // end of __set()
  
  public function __get($name)
  {
    switch ($name) {
      case 'required_authority':
      case 'service_command':
      case 'service_type':
        return $this->$name;
      default:
        throw new WebServiceException("WebService::__get($name): Undefined attribute '$name'");
    }
  } // end of __get()
  
  private function object_content_helper()
  {
    switch ($this->service_type) {
      case 'json': $this->content = json_encode($this->content); break;
      case 'xml': $this->content = $this->array_to_xml($this->content) ; break;
      default: throw new Exception("Fix Me");
    }
  } // end of render_array()
  
  public function render()
  {
    switch ($this->service_type) {
      case 'json': header("Content-Type: application/json");
        break;
      case 'xml': header("Content-Type: application/xml");
        break;
    }

    // common
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    header("Content-Length: " . strlen($this->content));
    echo $this->content;
  } // end of render()

  public function obj_to_array($obj)
  {
    $ar = array();
    if ($obj instanceof AnInstance) {
      foreach ($obj->attributes as $attr) {
        if ($obj->has_prop($attr, 'public')) {
          $ar[$attr] = $obj->$attr;
        }
      }
    }
    return $ar;
  } // end of obj_to_array()
  
  // formatting routines
  public function array_to_xml($name, $ar)
  {
    $str = "<?xml version=\"1.0\"?>\n<$name>\n";
    foreach ($ar as $key => $val) {
      $str .= "  <$key>$val</$key>\n";
    }
    return $str . "</$name>\n";
  } // end of array_to_xml()
  
  public function encode_rss()
  {
    // head of XML package
    $str = "<?xml version=\"1.0\"?>\n";
    $str .= "<rss version]\"2.0\">\n";
    
    // start of channel
    $str .= " <channel>\n";

    // end of channel
    $str .= " </channel>\n";

    // end rss tag
    $str .= "</rss>\n";
    return $str;
  } // end of encode_rss()
}

if (Globals::$rc->safe_get_service_type == 'rss') {
  class Rss20 {
    private $elements;
    public function __construct()
    {
      $elements['rss'] = array('required_elements' => array('channel'),
        'required_attributes' => array('version'));
      $elements['channel'] = array(
        'required_elements' => array('title', 'link', 'description'),
        'optional_elements' => array(
            'language',         // W3C language abrevaition
            'copyright',        // full text copyright
            'managingEditor',   // email address of person responsible for editorial content
            'webMaster',        // email address
            'pubDate',          // RFC 822
            'lastBuildDate',    // last time content of channel changed
            'category',         // see <item>-level category element
            'generator',        // program used to generate content: 'YASiteKit
            'docs',             // http://blogs.law.harvard.edu/tech/rss
            'cloud',            // <cloud domain="rpc.sys.com" port="80" path="/RPC2" registerProcedure="pingMe" protocol="soap"/>
            'ttl',              // time tolive in minutes - caching limit
            'image',            // channel image (URL?)
            'rating',           // PICS rating for channel (?)
            'textInput',        // text input box - displayable with channel
            'skipHours',        // hint for aggregators - telling how many hours to skip
            'skipDays',         // hint for aggregators
        ) );
      $elements['image'] = array(
            'required_elements' => array('url', 'title', 'link'),
            'optional_elements' => array('width', 'height', 'description'));
      $elements['textInput'] = array('required_elements' => array('title', 'description', 'name', 'link'));
      $elements['item'] = array( 'optional_elements' => array(
          'author', 'category', 'comments', 'description', 'enclosure', 'guid',
          'link', 'pubDate', 'source', 'title', ) );
      $elements['source'] = array( 'required_elements' => array('url'),);
      $elements['enclosure'] = array('required_attributes' => array('url', 'length', 'type'));
      $elements['category'] = array('optional_attributes' => array('domain'));
      $elements['guid'] = array('optional_attributes' => array('isPermaLink'));

      // brilliant stuff which initializes the structure
    } // end of __construct()
  
    public function render()
    {
      $str = "<?xml version=\"1.0\"?>\n<rss version=\"2.0\">\n <channel>\n";

      return $str . " </channel>\n</rss>\n";
    } // end of render()
  }
}

// end class definition

function fail($status_code, $reason_phrase, $content_type = "text/plain", $content = '')
{
  $reason_phrase = preg_replace('/\s+/', ' ', $reason_phrase);
  header("HTTP/1.1 $status_code $reason_phrase");
  header("Content-Type: $content_type");
  header("Content-Length: " . strlen($content));
  echo $content;
  exit(1);
} // end of fail()

// preliminary screen
if (Globals::$flag_is_robot) {
  fail('403', 'Forbidden');
}

if (!Globals::$flag_cookies_ok) {
  fail('412', 'Cookies Required');
}

if (!(Globals::$session_obj instanceof Session)) {
  fail('412', 'Session Required');
}
if (!(Globals::$account_obj instanceof Account)) {
  fail('412', 'Account Required');
}

// start of globals
Globals::$web_service = new WebService(Globals::$rc->safe_request_service_type, Globals::$page_name);
// end of globals

// function definitions

if (Globals::$web_service->service_type == 'xml') {

  
} elseif (Globals::$web_service->service_type == 'json') {
  function finish($result, $explanation = '', $ar = array())
  {
    echo json_encode(array('site_tag' => Globals::$site_tag, 'result' => $result, 'explanation' => $explanation,
      'object_count' => count($ar), 'object_ar' => $ar));
  } // end of finish()
}

// end function definitions

// dispatch actions
Globals::$web_service->render();

?>
