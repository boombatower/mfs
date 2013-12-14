<?php

namespace boombatower\mfs;

use org\bovigo\vfs\Quota;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;

class mfsStreamWrapper extends vfsStreamWrapper
{
  public static function register()
  {
    self::$root  = null;
    static::setQuota(Quota::unlimited());
    if (true === self::$registered) {
      return;
    }

    if (@stream_wrapper_register(mfsStream::SCHEME, __CLASS__) === false) {
      throw new vfsStreamException('A handler has already been registered for the ' . mfsStream::SCHEME . ' protocol.');
    }

    self::$registered = true;
  }

  public function stream_open($path, $mode, $options, $opened_path)
  {
    $extended = ((strstr($mode, '+') !== false) ? (true) : (false));
    $mode     = str_replace(array('b', '+'), '', $mode);
    if (in_array($mode, array('r', 'w', 'a', 'x', 'c')) === false) {
      if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
        trigger_error('Illegal mode ' . $mode . ', use r, w, a, x  or c, flavoured with b and/or +', E_USER_WARNING);
      }

      return false;
    }

    $this->mode    = $this->calculateMode($mode, $extended);
    $path          = $this->resolvePath(mfsStream::path($path));
    $this->content = $this->getContentOfType($path, vfsStreamContent::TYPE_FILE);
    if (null !== $this->content) {
      if (self::WRITE === $mode) {
        if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
          trigger_error('File ' . $path . ' already exists, can not open with mode x', E_USER_WARNING);
        }

        return false;
      }

      if (
        (self::TRUNCATE === $mode || self::APPEND === $mode) &&
        $this->content->isWritable(mfsStream::getCurrentUser(), mfsStream::getCurrentGroup()) === false
      ) {
        return false;
      }

      if (self::TRUNCATE === $mode) {
        $this->content->openWithTruncate();
      } elseif (self::APPEND === $mode) {
        $this->content->openForAppend();
      } else {
        if (!$this->content->isReadable(mfsStream::getCurrentUser(), mfsStream::getCurrentGroup())) {
          if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
            trigger_error('Permission denied', E_USER_WARNING);
          }
          return false;
        }
        $this->content->open();
      }

      return true;
    }

    $content = $this->createFile($path, $mode, $options);
    if (false === $content) {
      return false;
    }

    $this->content = $content;
    return true;
  }

  private function createFile($path, $mode = null, $options = null)
  {
    $names = $this->splitPath($path);
    if (empty($names['dirname']) === true) {
      if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
        trigger_error('File ' . $names['basename'] . ' does not exist', E_USER_WARNING);
      }

      return false;
    }

    $dir = $this->getContentOfType($names['dirname'], vfsStreamContent::TYPE_DIR);
    if (null === $dir) {
      if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
        trigger_error('Directory ' . $names['dirname'] . ' does not exist', E_USER_WARNING);
      }

      return false;
    } elseif ($dir->hasChild($names['basename']) === true) {
      if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
        trigger_error('Directory ' . $names['dirname'] . ' already contains a director named ' . $names['basename'], E_USER_WARNING);
      }

      return false;
    }

    if (self::READ === $mode) {
      if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
        trigger_error('Can not open non-existing file ' . $path . ' for reading', E_USER_WARNING);
      }

      return false;
    }

    if ($dir->isWritable(mfsStream::getCurrentUser(), mfsStream::getCurrentGroup()) === false) {
      if (($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS) {
        trigger_error('Can not create new file in non-writable path ' . $names['dirname'], E_USER_WARNING);
      }

      return false;
    }

    return mfsStream::newFile($names['basename'])->at($dir);
  }

  public function mkdir($path, $mode, $options)
  {
    $umask = mfsStream::umask();
    if (0 < $umask) {
      $permissions = $mode & ~$umask;
    } else {
      $permissions = $mode;
    }

    $path = $this->resolvePath(mfsStream::path($path));
    if (null !== $this->getContent($path)) {
      trigger_error('mkdir(): Path memecached://' . $path . ' exists', E_USER_WARNING);
      return false;
    }

    if (null === self::$root) {
      self::$root = mfsStream::newDirectory($path, $permissions);
      return true;
    }

    $maxDepth = count(explode('/', $path));
    $names    = $this->splitPath($path);
    $newDirs  = $names['basename'];
    $dir      = null;
    $i        = 0;
    while ($dir === null && $i < $maxDepth) {
      $dir     = $this->getContent($names['dirname']);
      $names   = $this->splitPath($names['dirname']);
      if (null == $dir) {
        $newDirs = $names['basename'] . '/' . $newDirs;
      }

      $i++;
    }

    if (null === $dir
      || $dir->getType() !== vfsStreamContent::TYPE_DIR
      || $dir->isWritable(mfsStream::getCurrentUser(), mfsStream::getCurrentGroup()) === false) {
      return false;
    }

    $recursive = ((STREAM_MKDIR_RECURSIVE & $options) !== 0) ? (true) : (false);
    if (strpos($newDirs, '/') !== false && false === $recursive) {
      return false;
    }

    mfsStream::newDirectory($newDirs, $permissions)->at($dir);
    return true;
  }
}
