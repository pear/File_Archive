<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PHP versions 4 and 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA
 *
 * @category   File Formats
 * @package    File_Archive
 * @author     Vincent Lascaux <vincentlascaux@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/File_Archive
 */

require_once "File/Archive/Writer.php";

/**
  * Writer to files
  */
class File_Archive_Writer_Files extends File_Archive_Writer
{
    /**
      * @var Object Handle to the file where the data are currently written
      */
    var $handle = null;

    /**
      * Ensure that $pathname exists, or create it if it does not
      */
    function mkdirr($pathname)
    {
        // Check if directory already exists
        if (is_dir($pathname) || empty($pathname)) {
            return true;
        }

        // Ensure a file does not already exist with the same name
        if (is_file($pathname)) {
            trigger_error('mkdirr() File exists', E_USER_WARNING);
            return false;
        }

        // Crawl up the directory tree
        $next_pathname = substr($pathname, 0, strrpos($pathname, DIRECTORY_SEPARATOR));
        if ($this->mkdirr($next_pathname)) {
            if (!file_exists($pathname)) {
                return mkdir($pathname);
            }
        }

        return false;
    }

    function newFile($filename, $stat, $mime="application/octet-stream")
    {
        if($this->handle !== NULL) {
            fclose($this->handle);
        }

        $pos = strrpos($filename, "/");
        if($pos !== false) {
            $this->mkdirr(substr($filename, 0, $pos));
        }
        $this->handle = fopen($filename, "w");
    }
    function writeData($data) { fwrite($this->handle, $data); }
    function close()
    {
        if($this->handle !== null) {
            fclose($this->handle);
        }
        $this->handle = null;
    }
}

?>