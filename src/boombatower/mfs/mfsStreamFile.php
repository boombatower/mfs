<?php

namespace boombatower\mfs;

use org\bovigo\vfs\vfsStreamFile;

class mfsStreamFile extends vfsStreamFile
{
  use mfsStreamContentTrait;

  public function open()
  {
    parent::open();
    $this->store();
  }

  public function openForAppend()
  {
    parent::openForAppend();
    $this->store();
  }

  public function openWithTruncate()
  {
    parent::openWithTruncate();
    $this->store();
  }

  public function write($data)
  {
    $return = parent::write($data);
    $this->store();
    return $return;
  }

  public function lock($resource, $operation)
  {
    $return = parent::lock($resource, $operation);
    $this->store();
    return $return;
  }

  public function unlock($resource)
  {
    parent::unlock($resource);
  }

  public function store()
  {
    MemcachedUtil::set($this);
  }
}
