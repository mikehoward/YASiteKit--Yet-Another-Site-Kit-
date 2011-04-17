<?php
/*
#begin-doc
h2. YAStream - base code from and modified the PHP manual VariableStream example

You don't really use YAStream or YAMemFile objects directly. What you do is include
this file, which registers the "var" protocol with PHP Streams and
then you 'simply' open files of the form "var://_some_path_" and
everything works like magic.

The rest of this document describes the internals - for the curious.

h2. Internals

*YAStream* implements a 'var' protocol for the PHP streams interface.

We implement two objects:

* YAMemFile - a path accessable file-like in-memory object.
* YAStream - a PHP stream wrapper which implements the 'var://path' stream
protocol.

The code here is based on, but modifies and extends the VariableStream example
in the Streams section of the PHP manual.

h2(#yamemfile). YAMemFile

A *YAMemFile* object is similar to a UNIX file except that:

* it does not support directories, nor any directory operations
* it does not support 'sparce' writes
* it does not support locking
* it is impermenent - once the process it is created in ends, it
evaporates.
* parameters to various functions differ from UNIX low level I/O
standards

It supports multiple, simultaneous access. PHP is sequentially executed,
so contention is pretty much impossible, unless you know what you
are doing and intentionally create it.

h3. Instantiation

*get_yasmemfile_var()* is a factory function which will find or (by default)
create a YAMemFile instance in the YAMemFile cache. This allows multiple,
independent access to the same YAMemFile instance.

pre. $foo = YAMemFile::get_yasmemfile_var($varname, $create_ok = TRUE);

*get_yasmemfile_var()* is a factory function which will find or (by default)
create a YAMemFile instance in the YAMemFile cache.

The '$create_ok' argument is used when opening an instance in 'x' mode and
during 'unlink' operations.

h3. Attributes

* path - string - path to this YAMemFile instance
* content - string - the content of the instance
* uid - int - UNIX style uid - owner of this instance
* gid - int - UNIX style gid - group of the owner of this instance
* mode - int - UNIX style mode - defaults to 0644
* atime - int - UNIX time stamp - last access time
* mtime - int - UNIX time stamp - last modification time
* ctime - int - UNIX time stamp - creation time
* blksize - int - defaults to 512
* size - int - strlen(content)
* blocks - int - number of blocks required for 'content'

h3. Class Methods

* rename($from, $to) - renames the instance _$from_ to _$to_ if possible.
Fails if _$from_ does not exist or _$to_ does. Returns TRUE on success, else FALSE
* unlink($varname) - deletes _$varname_ if it is in the cache and open_count is 0; then returns TRUE.
Otherwise returns FALSE.

h3. Instance Methods

usual magic methods

* open() - Increments open count and sets atime.
* close() - decrements open count
* read_data($offset, $count) - returns (at most) _$count_ data characters
from _$this_ beginning at offset _$offset_. Both must be non-negative integers.
This simply returns _substr()_, so the returned string may be empty or less
than _$count_ characters long.
* write_data($offset, $data) - attempts to write the _$data
beginning _$offset_ characters from the head of the buffer. If the
current content is less than _$offset_ characters, then the data will be
appended. Returns the number of characters written. Fails and returns 0
if _$offset_ is <= 0 or _$data_ is empty
* truncate($position) - if _$position_ < 0, returns FALSE. Otherwise,
discards all content beyond _$position_ and returns TRUE. NOTE: if
_$position_ < _$this->size_, does nothing.
* stat() - returns the status array - see PHP _stat()_ function.

h2(#yastream). YAStream

YAStream is the glue which connects YAMemFile objects to the PHP Streams
protocol, using the "var" URI protocol.

The file simply defines an object which implements _stream_open()_, _stream_close()_,
_stream_read()_, _stream_write()_, _stream_flush()_, _stream_tell()_, _stream_eof()_,
_stream_seek()_, and _stream_stat()_ as required by the streamWrapper class.
See "streamWrapper":http://www.php.net/manual/en/class.streamwrapper.php for
details.

#end-doc
*/

class YAMemFileException extends Exception {}

class YAMemFile {
  static private $cache = array();
  static private $next_inode = 1;
  private $dev = 100;   // this is an arbitray number. It should NOT conflict with any dev number in /dev
  private $inode = 0;
  private $varname;
  private $content = '';
  private $uid = 0;
  private $gid = 0;
  private $mode = 0644;
  private $atime = FALSE;
  private $mtime = FALSE;
  private $ctime = FALSE;
  private $blksize = 512;
  private $open_count = 0;

  private function __construct($varname) {
    $this->varname = $varname;
    $this->uid = getmyuid();
    $this->gid = getmygid();
    $this->ctime = time();
    $this->inode = YAMemFile::$next_inode;
    YAMemFile::$next_inode += 1;
  } // end of __construct()
  
  public function get_yamemfile_var($varname, $create_ok = TRUE) {
    if (!array_key_exists($varname, YAMemFile::$cache)) {
      if ($create_ok) {
        YAMemFile::$cache[$varname] = new YAMemFile($varname);
      } else {
        return FALSE;
      }
    }
    $var = YAMemFile::$cache[$varname];
    return $var;
  } // end of get_yamemfile_var()

  public function open() {
    $this->atime = time();
    $this->open_count += 1;
  } // end of open()
  
  public function close() {
    $this->open_count -= 1;
  } // end of close()

  public function __toString() {
    return $this->content;
  } // end of __toString()
  
  public function __get($name) {
    switch ($name) {
      case 'content':
        $this->atime = time();
      case 'varname':
      case 'dev':
      case 'inode':
      case 'uid':
      case 'gid':
      case 'mode':
      case 'atime':
      case 'mtime':
      case 'ctime':
      case 'blksize':
      case 'open_count':
        return $this->$name;
      case 'rdev':
        return $this->dev;
      case 'ino':
        return $this->inode;
      case 'nlink':
        return $this->open_count;
      case 'size':
        return strlen($this->content);
      case 'blocks':
        return $this->content ? intval($this->size / $this->blksize) + 1 : 0;
      default:
        throw new YAMemFileException("YAMemFile::__get($name): Illegal attribute");
    }
  } // end of __get()
  
  public function __set($name, $value) {
    switch ($name) {
      case 'content':
        $this->atime =
          $this->mtime = time();
        $this->$name = $value;
        break;
      case 'uid':
      case 'gid':
      case 'mode':
      case 'atime':
      case 'mtime':
      case 'ctime':
      case 'blksize':
        $this->$name = intval($value);
        break;
      case 'size':
      case 'blocks':
        break;
      case 'varname':
      case 'open_count':
      case 'dev':
      case 'inode':
        throw new YAMemFileException("YAMemFile::__set($name): attempt to set read only variable '$name'");
      default:
        throw new YAMemFileException("YAMemFile::__get($name): Illegal attribute");
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name) {
      case 'varname':
      case 'content':
      case 'uid':
      case 'gid':
      case 'mode':
      case 'atime':
      case 'mtime':
      case 'ctime':
      case 'blksize':
        return isset($this->$name);
      case 'size':
      case 'blocks':
      case 'open_count':
      case 'dev':
      case 'inode':
        return TRUE;
      default:
        throw new YAMemFileException("YAMemFile::__get($name): Illegal attribute");
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name) {
      case 'varname':
      case 'content':
      case 'uid':
      case 'gid':
      case 'mode':
      case 'atime':
      case 'mtime':
      case 'ctime':
      case 'blksize':
      case 'size':
      case 'blocks':
      case 'open_count':
      case 'dev':
      case 'inode':
        throw new YAMemFileException("YAMemFile::__unset($name): cannot unset '$name'");
      default:
        throw new YAMemFileException("YAMemFile::__get($name): Illegal attribute");
    }
  } // end of __unset()

  public function read_data($offset, $count) {
    $this->atime = time();
    return substr($this->content, $offset, $count);
  } // end of read_data()
  
  public function write_data($offset, $data) {
    $data_len = strlen($data);
    if ($data_len == 0 || $offset < 0) {
      return 0;
    } elseif ($offset >= $this->size) {
      $this->content .= $data;
    } else {
      $this->content = substr($this->content, 0, $offset) . $data
          . substr($this->content, $offset + $data_len);
    }
    $this->atime = time();
    $this->mtime = time();
    return $data_len;
  } // end of write_data()
  
  public static function unlink($varname) {
    $var = YAMemFile::get_yamemfile_var($varname, FALSE);
    if ($var) {
      if ($var->open_count <= 0) {
        unset(YAMemFile::$cache[$var->varname]);
        return TRUE;
      } else {
        return FALSE;
      }
    } else {
      return FALSE;
    }
  } // end of unlink()

  public static function rename($from, $to) {
    if (array_key_exists($from, YAMemFile::$cache)) {
      if (array_key_exists($to, YAMemFile::$cache)) {
        return FALSE;
      } else {
        YAMemFile::$cache[$to] = YAMemFile::$cache[$from];
        YAMemFile::$cache[$to]->varname = $to;
        unset(YAMemFile::$cache[$from]);
        return TRUE;
      }
    } else {
      return FALSE;
    }
  } // end of rename()

  public function truncate($position = 0) {
    if ($position < 0) {
      return FALSE;
    } elseif ($this->size > $position) {
      $this->content = $position ? substr($this->content, 0, $position) : '';
      $this->size = $position;
      $this->atime = time();
      $this->mtime == time();
    }
    return TRUE;
  } // end of truncate()
  
  public function stat() {
    return array(
    $this->dev,                                 // 0  dev   device number
    $this->inode,                               // 1  ino   inode number *
    $this->mode,                                // 2  mode  inode protection mode
    $this->open_count,                          // 3  nlink   number of links
    $this->uid,                                 // 4  uid   userid of owner *
    $this->gid,                                 // 5  gid   groupid of owner *
    $this->rdev,                                // 6  rdev  device type, if inode device
    $this->size,                                // 7  size  size in bytes
    $this->atime,                               // 8  atime   time of last access (Unix timestamp)
    $this->mtime,                               // 9  mtime   time of last modification (Unix timestamp)
    $this->ctime,                               // 10   ctime   time of last inode change (Unix timestamp)
    $this->blksize,                             // 11   blksize   blocksize of filesystem IO **
    $this->blocks,                              // 12   blocks  number of 512-byte blocks allocated **
    'dev' => $this->dev,                        // 0  dev   device number
    'ino' => $this->inode,                      // 1  ino   inode number *
    'mode' => $this->mode,                      // 2  mode  inode protection mode
    'nlink' => $this->open_count,               // 3  nlink   number of links
    'uid' => $this->uid,                        // 4  uid   userid of owner *
    'gid' => $this->gid,                        // 5  gid   groupid of owner *
    'rdev' => $this->rdev,                      // 6  rdev  device type, if inode device
    'size' => $this->size,                      // 7  size  size in bytes
    'atime' => $this->atime,                    // 8  atime   time of last access (Unix timestamp)
    'mtime' => $this->mtime,                    // 9  mtime   time of last modification (Unix timestamp)
    'ctime' => $this->ctime,                    // 10   ctime   time of last inode change (Unix timestamp)
    'blksize' => $this->blksize,                // 11   blksize   blocksize of filesystem IO **
    'blocks' => $this->blocks,                  // 12   blocks  number of 512-byte blocks allocated **
    );
  } // end of stat()
  
  public function dump($msg = '') {
    $str = $msg ? "$msg\n" : '';
    foreach ( array('varname', 'content', 'size', 'uid', 'gid', 'mode',
                    'atime', 'mtime', 'ctime',
                    'blksize', 'blocks', 'open_count',) as $attr ) {
      $str .= " $attr: {$this->$attr}\n";
    }
    return $str;
  } // end of dump()
}

class YAStreamException extends Exception {}

class YAStream {
  public $context = NULL;   // a resource
  
  static $variables = array();
  private $position;
  private $varname;
  private $var;
  private $append_mode = FALSE;
  private $read_mode = FALSE;
  private $write_mode = FALSE;

  public function __construct() {
    # code...
  } // end of __construct()

  function stream_open($path, $mode, $options, &$opened_path)
  {
    $url = parse_url($path);
    $this->varname = $url["host"];
    switch ($mode[0]) {
      case 'r':
        $this->var = YAMemFile::get_yamemfile_var($this->varname);
        $this->position = 0;
        $this->read_mode = TRUE;
        break;
      case 'w':
        $this->var = YAMemFile::get_yamemfile_var($this->varname);
        $this->position = 0;
        $this->write_mode = TRUE;
        $this->var->truncate();
        break;
      case 'a':
        $this->var = YAMemFile::get_yamemfile_var($this->varname);
        $this->append_mode = TRUE;
        $this->write_mode = TRUE;
        $this->position = $this->var->size;
        break;
      case 'x':
        // get stream with $create_ok == FALSE
        if (!($this->var = YAMemFile::get_yamemfile_var($this->varname, FALSE))) {
          return FALSE;
        }
        $this->position = 0;
        break;
      case 'c':
        $this->var = YAMemFile::get_yamemfile_var($this->varname);
        $this->position = 0;
        break;
    }
// echo $this->var->dump(__FILE__.":".__LINE__ . "$path, $mode");
    if (preg_match('/\+/', $mode)) {
      $this->read_mode =
        $this->write_mode = TRUE;
    }
    // check read/write permissions
    if ($this->read_mode) {
      if ( ! ( (getmyuid() == $this->var->uid && ($this->var->mode & 0400))
                || (getmygid() == $this->var->gid && ($this->var->mode & 0040))
                || ($this->var->mode & 0004))) {
        return FALSE;
      }
    }
    if ($this->write_mode) {
      if ( ! ( (getmyuid() == $this->var->uid && ($this->var->mode & 0200))
                || (getmygid() == $this->var->gid && ($this->var->mode & 0020))
                || ($this->var->mode & 0002))) {
        return FALSE;
      }
    }

    $this->var->open();

    return TRUE;
  }

  public function stream_close() {
    $this->var->close();
  } // end of stream_close()

  function stream_read($count)
  {
    if ($this->read_mode) {
      $ret = $this->var->read_data($this->position, $count);
      $this->position += strlen($ret);
      return $ret;
    } else {
      return FALSE;
    }
  }

  function stream_write($data)
  {
    if ($this->write_mode) {
      $write_len = $this->var->write_data($this->append_mode ? $this->var->size : $this->position, $data);
      $this->position += $write_len;
      return $write_len;
    } else {
      return 0;
    }
  }

  public function stream_flush() {
    return TRUE;
  } // end of stream_flush()

  function stream_tell()
  {
    return $this->position;
  }

  function stream_eof()
  {
    return $this->position >= $this->var->size;
  }

  function stream_seek($offset, $whence)
  {
    switch ($whence) {
      case SEEK_SET:
        $new_position = $offset;
        break;
      case SEEK_CUR:
        $new_position = $this->position + $offset;
        break;
      case SEEK_END:
        $new_position = $this->var->size + $offset;
        break;
      default:
        return FALSE;
    }
    if ($new_position < 0) {
      return FALSE;
    }
    $this->position = $new_position > $this->var->size ? $this->var->size : $new_position;
    return TRUE;
  }
  
  public function stream_stat() {
    return $this->var->stat();
  } // end of stat()
  
  public function url_stat($path, $flags = NULL) {
    $url = parse_url($path);
    $varname = $url["host"];
    if (($var = YAMemFile::get_yamemfile_var($varname, FALSE))) {
      return $var->stat();
    } else {
      return FALSE;
    }
  } // end of url_stat()

  public function rename($from, $to) {
    return YAMemFile::rename($from, $to);
  } // end of stream_rename()
  
  public function unlink($path) {
    // set create_ok to FALSE so we don't pointelessly create a var
    $url = parse_url($path);
    $varname = $url["host"];
    return YAMemFile::unlink($varname);
  } // end of unlink()
}

if (in_array('var', stream_get_wrappers())) {
  stream_wrapper_unregister("var");
}
stream_wrapper_register("var", "YAStream")
    or die("Failed to register protocol");
