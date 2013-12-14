<?php

namespace boombatower\mfs;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamVisitor;

class mfsStream extends vfsStream
{
  const SCHEME = 'mfs';

  public static function setup($rootDirName = 'root', $permissions = null, array $structure = array())
  {
    mfsStreamWrapper::register();

    $directory = MemcachedUtil::get(static::SCHEME . '://' . $rootDirName) ?:
      static::newDirectory($rootDirName, $permissions);
    return static::create($structure, mfsStreamWrapper::setRoot($directory));
  }

  public static function create(array $structure, vfsStreamDirectory $baseDir = null)
  {
    if (null === $baseDir) {
      $baseDir = mfsStreamWrapper::getRoot();
    }

    if (null === $baseDir) {
      throw new \InvalidArgumentException('No baseDir given and no root directory set.');
    }

    return static::addStructure($structure, $baseDir);
  }

  public static function copyFromFileSystem($path, vfsStreamDirectory $baseDir = null, $maxFileSize = 1048576)
  {
    if (null === $baseDir) {
      $baseDir = mfsStreamWrapper::getRoot();
    }

    if (null === $baseDir) {
      throw new \InvalidArgumentException('No baseDir given and no root directory set.');
    }

    return parent::copyFromFileSystem($path, $baseDir, $maxFileSize);
  }

  public static function inspect(vfsStreamVisitor $visitor, vfsStreamContent $content = null)
  {
    $root = mfsStreamWrapper::getRoot();
    if (null === $root) {
      throw new \InvalidArgumentException('No content given and no root directory set.');
    }

    return parent::inspect($visitor, $content);
  }

  public static function newFile($name, $permissions = null)
  {
    return new mfsStreamFile($name, $permissions);
  }

  public static function newDirectory($name, $permissions = null)
  {
    if ('/' === $name{0}) {
      $name = substr($name, 1);
    }

    $firstSlash = strpos($name, '/');
    if (false === $firstSlash) {
      return new mfsStreamDirectory($name, $permissions);
    }

    $ownName   = substr($name, 0, $firstSlash);
    $subDirs   = substr($name, $firstSlash + 1);
    $directory = new mfsStreamDirectory($ownName, $permissions);
    static::newDirectory($subDirs, $permissions)->at($directory);
    return $directory;
  }

  public static function setQuota($bytes)
  {
    mfsStreamWrapper::setQuota(new Quota($bytes));
  }

  public static function url($path)
  {
    return static::SCHEME . '://' . str_replace('\\', '/', $path);
  }
}
