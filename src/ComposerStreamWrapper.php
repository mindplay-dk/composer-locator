<?php

namespace mindplay\composer_locator;

use ComposerLocator;

class ComposerStreamWrapper
{
    /**
     * RegEx pattern, matches `composer://vendor/package` or `composer://vendor/package/path`, etc.
     */
    const PACKAGE_PATH_PATTERN = '/^[^\:]+:\/\/(?\'package\'[^\/]+\/[^\/]+)(?\'path\'.*)/';

    /**
     * RegEx pattern, matches `composer://` or `composer://vendor`
     */
    const PARTIAL_PATH_PATTERN = '/^[^\:]+:\/\/(?\'vendor\'[^\/]*)\/*$/';

    /**
     * @var string
     */
    private $path;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var string[]
     */
    private $dir;

    /**
     * @see https://www.php.net/manual/en/streamwrapper.stream-open.php
     * 
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string &$opened_path
     * 
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if (1 === preg_match('/^[^\:]+:\/\/(?\'package\'[^\/]+\/[^\/]+)(?\'path\'.*)/', $path, $matches)) {
            $dir = ComposerLocator::getPath($matches["package"]);

            $opened_path = $dir . $matches["path"];

            $this->path = $opened_path;

            $this->resource = fopen($this->path, $mode);

            return true;
        }

        return false;
    }

    /**
     * @see https://www.php.net/manual/en/streamwrapper.stream-read.php
     * 
     * @param int $count
     * 
     * @return string
     */
    public function stream_read($count)
    {
        return fread($this->resource, $count);
    }

    /**
     * @see https://www.php.net/manual/en/streamwrapper.stream-eof.php
     * 
     * @return bool
     */
    public function stream_eof()
    {
        return feof($this->resource);
    }

    /**
     * @see https://www.php.net/manual/en/streamwrapper.stream-stat.php
     * 
     * @return array|false
     */
    public function stream_stat()
    {
        return stat($this->path);
    }

    /**
     * @see https://www.php.net/manual/en/streamwrapper.url-stat.php
     * 
     * @param string $path
     * @param int    $flags
     */
    public function url_stat($path, $flags)
    {
        static $virtual_node_stat;

        if (is_null($virtual_node_stat)) {
            $root = ComposerLocator::getRootPath();
            $mode = 040444; // read-only directory
            $atime = fileatime($root);
            $mtime = filemtime($root);
            $ctime = filectime($root);

            $virtual_node_stat = [
                0 => 0, "dev" => 0,
                1 => 0, "ino" => 0,
                2 => $mode, "mode" => $mode,
                3 => 0, "nlink" => 0,
                4 => 0, "uid" => 0,
                5 => 0, "gid" => 0,
                6 => 0, "rdev" => 0,
                7 => 0, "size" => 0,
                8 => $atime, "atime" => $atime,
                9 => $mtime, "mtime" => $mtime,
                10 => $ctime, "ctime" => $ctime,
                11 => 0, "blksize" => 0,
                12 => 0, "blocks" => 0            
            ];
        }

        return 1 === preg_match(self::PARTIAL_PATH_PATTERN, $path, $matches)
            ? $virtual_node_stat
            : stat($this->resolvePath($path));
    }
    
    /**
     * @see http://www.php.net/manual/en/streamwrapper.dir-opendir.php
     *
     * @param string $path
     * @param int    $options
     *
     * @return bool
     */
    public function dir_opendir($path, $options) {
        if (1 === preg_match(self::PARTIAL_PATH_PATTERN, $path, $matches)) {
            if ($matches["vendor"]) {
                $dir = [".", ".."];

                $prefix = "{$matches["vendor"]}/";
                $offset = strlen($prefix);

                foreach (ComposerLocator::getPackages() as $package) {
                    if (false !== stripos($package, $prefix)) {
                        $dir[] = substr($package, $offset);
                    }
                }

                $this->dir = $dir;
            } else {
                static $vendors;
                
                if (is_null($vendors)) {
                    $vendors = [".", ".."];

                    foreach (ComposerLocator::getPackages() as $package) {
                        $vendors[] = substr($package, 0, stripos($package, "/"));
                    }

                    $vendors = array_unique($vendors);
                }

                $this->dir = $vendors;
            }

            return true;
        }

        $dir = opendir($this->resolvePath($path));

        if (is_resource($dir)) {
            $this->dir = [];

            while (false !== ($path = readdir($dir))) {
                $this->dir[] = $path;
            }

            return true;
        }
    }

    /**
     * @see http://www.php.net/manual/en/streamwrapper.dir-closedir.php
     *
     * @return bool
     */
    public function dir_closedir() {
        if ($this->dir) {
            $this->dir = null;

            return true;
        }

        return false;
    }

    /**
     * @see http://www.php.net/manual/en/streamwrapper.dir-readdir.php
     *
     * @return string|false
     */
    public function dir_readdir() {
        if ($this->dir) {
            $path = current($this->dir);
            
            next($this->dir);

            return $path;
        }

        return false;
    }

    /**
     * @see http://www.php.net/manual/en/streamwrapper.dir-rewinddir.php
     *
     * @return bool
     */
    public function dir_rewinddir() {
        if ($this->dir) {
            reset($this->dir);

            return true;
        }

        return false;
    }

    /**
     * @param string
     * 
     * @return string|null resolved path (or null, if the path could not be resolved)
     */
    private function resolvePath($path)
    {
        if (1 === preg_match(self::PACKAGE_PATH_PATTERN, $path, $matches)) {
            $dir = ComposerLocator::getPath($matches["package"]);

            return $dir . $matches["path"];
        }
    }
}