<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Compress a single file to Bzip2 format
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
 * Compress a single file to Bzip2 format
 */
class File_Archive_Writer_Bzip2 extends File_Archive_Writer_MemoryArchive
{
    var $compressionLevel=9;
    /**
     * Set the compression level
     *
     * @param int $compressionLevel From 0 (no compression) to 9 (best
     *        compression)
     */
    function setCompressionLevel($compressionLevel)
    {
        $this->compressionLevel = $compressionLevel;
    }

    /**
     * @see File_Archive_Writer::newFile()
     *
     * Check that one single file is written in the BZip2 archive
     */
    function newFile($filename, $stat = array(),
                     $mime = "application/octet-stream")
    {
        $result = parent::newFile($filename, $stat, $mime);
        if ($result !== true) {
            return $result;
        }
        if($this->nbFiles > 1) {
            return PEAR::raiseError("A Bzip2 archive can only contain one single file.".
                                    "Use Tbz archive to be able to write several files");
        }
        return true;
    }


    /**
     * @see File_Archive_Writer_MemoryArchive::appendFileData()
     */
    function appendFileData($filename, $stat, $data)
    {
        return $this->innerWriter->writeData(
            bzcompress($data, $this->compressionLevel)
        );
    }
}

?>