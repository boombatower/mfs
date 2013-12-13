<?php

namespace com\boombatower\vfs;

trait vfsStreamContentMemcachedTrait
{
  public function rename($newName)
  {
    parent::rename($newName);
    $this->store();
  }

  public function setParentPath($parentPath)
  {
    parent::setParentPath($parentPath);
    $this->store();
  }

  public function lastModified($filemtime)
  {
    $return = parent::lastModified($filemtime);
    $this->store();
    return $return;
  }

  public function lastAccessed($fileatime)
  {
    $return = parent::lastAccessed($fileatime);
    $this->store();
    return $return;
  }

  public function lastAttributeModified($filectime)
  {
    $return = parent::lastAttributeModified($filectime);
    $this->store();
    return $return;
  }

  public function chmod($permissions)
  {
    $return = parent::chmod($permissions);
    $this->store();
    return $return;
  }

  public function chown($user)
  {
    $return = parent::chown($user);
    $this->store();
    return $return;
  }

  public function chgrp($group)
  {
    $return = parent::chgrp($group);
    $this->store();
    return $return;
  }

  // Also handles truncate()
  public function setContent($content)
  {
    $return = parent::setContent($content);
    $this->store();
    return $return;
  }

  public function url()
  {
    return vfsStreamMemcached::url($this->path());
  }
}
