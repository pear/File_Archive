<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ZIP archive writer
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

require_once "MemoryArchive.php";

/**
 * ZIP archive writer
 */
class File_Archive_Writer_Zip extends File_Archive_Writer_MemoryArchive
{
    /**
     * @var int Compression level
     * @access private
     */
    var $compressionLevel = 9;

    /**
     * @var int Current position in the writer
     * @access private
     */
    var $offset = 0;

    /**
     * @var string Optionnal comment to add to the zip
     * @access private
     */
    var $comment = "";

    /**
     * @var string Data written at the end of the ZIP file
     * @access private
     */
    var $central = "";

    function File_Archive_Writer_Zip($filename, &$innerWriter, $stat=array(), $autoClose = true)
    {
        parent::File_Archive_Writer_MemoryArchive($filename, $innerWriter, $stat, $autoClose);
        function_exists('gzcompress') or die("GZ Compress function not available");
    }

    /**
     * Change the level of the compression
     * This may be done between two files
     * The compression level is an int from 0 (no compression) to 9 (best compression)
     * @param Int $compressionLevel New compression level from 0 to 9
     */
    function setCompressionLevel($compressionLevel) { $this->compressionLevel = $compressionLevel; }

    /**
     * Set a comment on the ZIP file
     */
    function setComment($comment) { $this->comment = $comment; }

    /**
     * @see File_Archive_Writer_MemoryArchive::appendFileData
     * @access protected
     */
    function appendFileData($filename, $stat, $data)
    {
        $filename = preg_replace("/^(\.{1,2}(\/|\\\))+/","",$filename);

        $mtime = (isset($stat[9])? getdate($stat[9]) : getdate());
        $mtime = preg_replace("/(..){1}(..){1}(..){1}(..){1}/","\\x\\4\\x\\3\\x\\2\\x\\1",dechex(($mtime['year']-1980<<25)|($mtime['mon']<<21)|($mtime['mday']<<16)|($mtime['hours']<<11)|($mtime['minutes']<<5)|($mtime['seconds']>>1)));
        eval('$mtime = "'.$mtime.'";');

        $crc32 = crc32($data);
        $normlength = strlen($data);
        $data = gzcompress($data,$this->compressionLevel);
        $data = substr($data,2,strlen($data)-6);
        $complength = strlen($data);

        $zipData = "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00".
                   $mtime.
                   pack("VVVvv",$crc32,$complength,$normlength,strlen($filename),0x00).
                   $filename.
                   $data;

        $error = $this->innerWriter->writeData($zipData);
        if(PEAR::isError($error)) {
            return $error;
        }

        $this->central .= "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00".
                   $mtime.
                   pack("VVVvvvvvVV",
                       $crc32, $complength, $normlength,
                       strlen($filename), 0x00,0x00,0x00,0x00,
                       0x0000,$this->offset).
                   $filename;

        $this->offset += strlen($zipData);

    }
    /**
     * @see File_Archive_Writer_MemoryArchive::sendFooter
     * @access protected
     */
    function sendFooter()
    {
        return $this->innerWriter->writeData(
            $this->central.
            "\x50\x4b\x05\x06\x00\x00\x00\x00".
            pack("vvVVv",
                $this->nbFiles,$this->nbFiles,
                strlen($this->central),$this->offset,
                strlen($this->comment)).
            $this->comment
        );
    }
    /**
     * @see File_Archive_Writer::getMime
     */
    function getMime() { return "application/zip"; }
}

?>