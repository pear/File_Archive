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

require_once "MemoryArchive.php";

class File_Archive_Writer_Gzip extends File_Archive_Writer_MemoryArchive
{
    var $gzdata = "";
    var $comment = "";
    var $compressionLevel = 9;

    function setComment($comment) { $this->comment = $comment; }
    function setCompressionLevel($compressionLevel) { $this->compressionLevel = $compressionLevel; }

    function appendFileData($filename, $stat, $data)
    {
        $flags = bindec("000".(!empty($this->comment)? "1" : "0").(!empty($filename)? "1" : "0")."000");
        $mtime = isset($stat[9]) ? $stat[9] : time();

        $this->innerWriter->writeData(
            pack("C1C1C1C1VC1C1",0x1f,0x8b,8,$flags,$mtime,2,0xFF).
            (empty($filename) ? "" : $filename."\0").
            (empty($this->comment) ? "" : $this->comment."\0").
            gzdeflate($data, $this->compressionLevel).
            pack("VV",crc32($data),strlen($data))
        );
    }
}

?>