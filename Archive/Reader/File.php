<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Reader that represents a single file
 *
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
require_once "MIME/Type.php";

/**
 * Reader that represents a single file
 */
class File_Archive_Reader_File extends File_Archive_Reader
{
    /**
     * @var object Handle to the file being read
     * @access private
     */
    var $handle = null;
    /**
     * @var string Name of the physical file being read
     * @access private
     */
    var $filename;
    /**
     * @var string Name of the file returned by the reader
     * @access private
     */
    var $symbolic;
    /**
     * @var array Stats of the file
     *            Will only be set after a call to $this->getStat()
     * @access private
     */
    var $stat = null;
    /**
     * @var File_Archive_Reader_Memory If we can't use stat on the URL, we need to read the whole
     *      file to compute its length. In this case $memory won't be null, and store the content
     *      of the file
     * @access private
     */
    var $memory = null;

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
     *
     * Close the file handle
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
     *
     * The first time next is called, it will open the file handle and return true
     * Then if will return false
     * Raise an error if the file does not exist
     */
    function next()
    {
        if($this->handle != null) {
            return false;
        }
        $this->handle = fopen($this->filename, "r");
        if(!is_resource($this->handle)) {
            return PEAR::raiseError("Can't open {$this->filename} for reading");
        }
        $this->memory = null;
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
     *
     * Return the name of the file
     */
    function getDataFilename() { return $this->filename; }
    /**
     * @see File_Archive_Reader::getStat() stat()
     */
    function getStat()
    {
        if($this->stat == null) {
            $this->stat = @stat($this->filename);

            //If we can't use the stat function
            if($this->stat === false) {
                $alreadyRead = ftell($this->handle);

                //Put the whole file content in memory
                require_once "Memory.php";
                $this->memory = new File_Archive_Reader_Memory($this->getData(), $this->symbolic);

                $this->stat = $this->memory->getStat();
                $this->stat = array(7 => $this->stat[7] + $alreadyRead);
            }
        }
        return $this->stat;
    }

    /**
     * @see File_Archive_Reader::getMime
     */
    function getMime()
    {
        $result = MIME_Type::autoDetect($this->getFilename());
        if(PEAR::isError($result)) {
            return parent::getMime();
        } else {
            return $result;
        }
    }

    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if($this->memory != null) {
            return $this->memory->getData($length);
        }

        if(feof($this->handle)) {
            return null;
        }
        if($length == -1) {
            $contents = '';
            while (!feof($this->handle)) {
                $contents .= fread($this->handle, 8192);
            }
            return $contents;
        } else {
            if($length == 0) {
                return "";
            } else {
                return fread($this->handle, $length);
            }
        }
    }
    /**
     * @see File_Archive_Reader::Skip()
     */
    function skip($length)
    {
        if($this->memory != null) {
            return $this->memory->skip($length);
        }

        $before = ftell($this->handle);
        if(@fseek($this->handle, $length, SEEK_CUR) === -1) {
            return parent::skip($length);
        } else {
            return ftell($this->handle) - $before;
        }
    }
}

?>