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

require_once "File/Archive/Reader.php";

/**
  * Reader that represents a single file
  */
class File_Archive_Reader_File extends File_Archive_Reader
{
    /**
      * @var Object Handle to the file being read
      */
    var $handle = null;
    /**
      * @var String Name of the physical file being read
      */
    var $filename;
    /**
      * @var String Name of the file returned by the reader
      */
    var $symbolic;

    /**
      * $filename is the physical file to read
      * $symbolic is the name declared by the reader
      * If $symbolic is not specified, $filename is assumed
      */
    function File_Archive_Reader_File($filename, $symbolic = null)
    {
        $this->filename = $filename;
        if($symbolic == null) {
            $this->symbolic = $this->getStandardURL($filename);
        } else {
            $this->symbolic = $this->getStandardURL($symbolic);
        }
    }
    /**
      * @see File_Archive_Reader::close()
      */
    function close()
    {
        if($this->handle != null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
    /**
      * @see File_Archive_Reader::next()
      */
    function next()
    {
        if($this->handle != null) {
            return false;
        }
        $this->handle = fopen($this->filename, "r");
        if($this->handle === false) {
            return PEAR::raiseError("File {$this->filename} not found");
        } else {
            return true;
        }
    }
    /**
      * @see File_Archive_Reader::getFilename()
      */
    function getFilename() { return $this->symbolic; }
    /**
      * @see File_Archive_Reader::getDataFilename()
      */
    function getDataFilename() { return $this->filename; }
    /**
      * @see File_Archive_Reader::getStat()
      */
    function getStat() { return stat($this->filename); }

    //TODO: use the PEAR library to find the MIME extension of the file
    // function getMime()

    /**
      * @see File_Archive_Reader::getData()
      */
    function getData($length = -1)
    {
        if(feof($this->handle)) {
            return null;
        }
        if($length == -1) {
            // filesize + 1 to prevent the fread(handle, 0) bug
            return fread($this->handle, filesize($this->filename)+1);
        } else {
            return fread($this->handle, $length);
        }
    }
    /**
      * @see File_Archive_Reader::Skip()
      */
    function skip($length)
    {
        $before = ftell($this->handle);
        fseek($this->handle, $length, SEEK_CUR);
        return ftell($this->handle) - $before;
    }
}

?>