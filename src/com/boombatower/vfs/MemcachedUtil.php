<?php

namespace com\boombatower\vfs;

use org\bovigo\vfs\vfsStreamContent;

class MemcachedUtil
{
  protected static $memcached;

  protected static function init()
  {
    if (!isset(static::$memcached)) {
      static::$memcached = new \Memcached();
    }
  }

  public static function get($url)
  {
    static::init();
    return static::$memcached->get($url);
  }

  public static function set(vfsStreamContent $content)
  {
    static::init();
    return static::$memcached->set($content->url(), $content);
  }
}
