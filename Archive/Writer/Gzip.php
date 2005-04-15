<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Compress a single file to Gzip format
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

require_once "Archive.php";

/**
 * Compress a single file to Gzip format
 */
class File_Archive_Writer_Gzip extends File_Archive_Writer_Archive
{
    var $comment = "";
    var $compressionLevel = 9;
    var $gzfile;
    var $tmpName;
    var $nbFiles = 0;

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
     * Check that one single file is written in the GZip archive
     */
    function newFile($filename, $stat = array(),
                     $mime = "application/octet-stream")
    {
        if($this->nbFiles > 1) {
            return PEAR::raiseError("A GZip archive can only contain one single file.".
                                    "Use Tbz archive to be able to write several files");
        }
        $this->nbFiles++;

        $this->tmpName = tempnam('.', 'far');
        $this->gzfile = gzopen($this->tmpName, 'w'.$this->compressionLevel);

        return true;
    }
    /**
     * Actually write the tmp file to the inner writer
     * Close and delete temporary file
     *
     * @see File_Archive_Writer::close();
     */
    function close()
    {
        gzclose($this->gzfile);
        $this->innerWriter->writeFile($this->tmpName);
        unlink($this->tmpName);

        return parent::close();
    }

    function writeData($data)
    {
        gzwrite($this->gzfile, $data);
    }
}

?>