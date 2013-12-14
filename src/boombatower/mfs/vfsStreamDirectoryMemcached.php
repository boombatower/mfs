<?php

namespace boombatower\mfs;

use org\bovigo\vfs\vfsStreamDirectory;

class vfsStreamDirectoryMemcached extends vfsStreamDirectory
{
  use vfsStreamContentMemcachedTrait;

  // addChild() calls updateModifications() which will trigger a store() call.

  public function removeChild($name)
  {
    $return = parent::removeChild($name);
    $this->store();
    return $return;
  }

  protected function updateModifications()
  {
    parent::updateModifications();
    $this->store();
  }

  public function getChild($name)
  {
    $childName = $this->getRealChildName($name);
    foreach ($this->children as &$child) {
      // Children are replaced with nulls during store() so the actual child
      // needs to be loaded.
      if ($child === null) {
        $child = MemcachedUtil::get($this->url() . '/' . $childName);
      }

      if ($child->getName() === $childName) {
        return $child;
      }

      if ($child->appliesTo($childName) === true && $child->hasChild($childName) === true) {
        return $child->getChild($childName);
      }
    }

    return null;
  }

  public function store()
  {
    // Clear children to avoid storing twice since each child will be stored in
    // a separate key via its own store() method.
    $directory = clone $this;
    foreach ($directory->children as &$child) {
      $child = null;
    }
    MemcachedUtil::set($directory);
  }
}
