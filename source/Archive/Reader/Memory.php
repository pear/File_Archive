<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: Vincent Lascaux <vincent.lascaux at centraliens.net>        |
// +----------------------------------------------------------------------+
//
// $Id$

require_once "File/Archive/Reader.php";

/**
  * A reader that takes its input from a memory buffer
  */
class File_Archive_Reader_Memory extends File_Archive_Reader
{
    /**
      * @var String Name of the file exported by this reader
      */
    var $filename;
    /**
      * @var Array Stat of the file exported by this reader
      */
    var $stat;
    /**
      * @var String MIME type of the file exported by this reader
      */
    var $mime;
    /**
      * @var String Memory buffer that contains the data of the file
      */
    var $memory;
    /**
      * @var Int Current position in the file
      */
    var $offset = 0;
    /**
      * @var Boolean Has the file already been read
      */
    var $alreadyRead = false;

    /**
      * $memory is the content of the file. The content should not be changer after the constructor
      * $filename and $stat are the caracteristics of the file contained in the reader
      */
    function File_Archive_Reader_Memory($memory, $filename, $stat=array(), $mime="application/octet-stream")
    {
        $this->memory = $memory;
        $this->filename = $this->getStandardURL($filename);
        $this->stat = $stat;
        $this->stat[7] = strlen($this->memory);
        $this->mime = $mime;
    }

    /**
      * The subclass should overwrite this function to change the filename, stat and memory
      */
    function next()
    {
        if($this->alreadyRead) {
            return false;
        } else {
            $this->alreadyRead = true;
            return true;
        }
    }

    function getFilename() { return $this->filename; }
    function getStat() { return $this->stat; }
    function getMime() { return $this->mime; }

    function getData($length = -1)
    {
        if($this->offset == strlen($this->memory)) {
            return null;
        }
        if($length == -1) {
            $actualLength = strlen($this->memory) - $this->offset;
        } else {
            $actualLength = min($length, strlen($this->memory) - $this->offset);
        }
        $result = substr($this->memory, $this->offset, $actualLength);
        $this->offset += $actualLength;
        return $result;
    }
    function skip($length)
    {
        $actualLength = min($length, strlen($this->memory) - $this->offset);
        $this->offset += $actualLength;
        return $actualLength;
    }
    function close()
    {
        $this->offset = 0;
        $this->alreadyRead = false;
    }
}

?>