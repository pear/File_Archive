<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Write the files as a TAR archive
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
 * Write the files as a TAR archive
 */
class File_Archive_Writer_Tar extends File_Archive_Writer_MemoryArchive
{
    /**
     * Creates the TAR header for a file
     *
     * @param string $filename name of the file
     * @param array $stat statistics of the file
     * @return string A 512 byte header for the file
     * @access private
     */
    function tarHeader($filename, $stat)
    {
        $mode = $stat[2];
        $uid  = $stat[4];
        $gid  = $stat[5];
        $size = $stat[7];
        $time = $stat[9];
        $link = "";

        if ($mode & 0x4000) {
            $type = 5;        // Directory
        } else if ($mode & 0x8000) {
            $type = 0;        // Regular
        } else if ($mode & 0xA000) {
            $type = 1;        // Link
            $link = @readlink($current);
        } else {
            $type = 9;        // Unknown
        }

        $pos = strrpos($filename, "/");
        if ($pos !== FALSE) {
            $path = substr($filename, 0, $pos+1);
            $path = preg_replace("/^(\.{1,2}(\/|\\\))+/","",$path);

            $file = substr($filename, $pos+1);
        } else {
            $path = "";
            $file = $filename;
        }

        $blockbeg = pack("a100a8a8a8a12a12",
            $file,
            decoct($mode),
            sprintf("%6s ",decoct($uid)),
            sprintf("%6s ",decoct($gid)),
            sprintf("%11s ",decoct($size)),
            sprintf("%11s ",decoct($time))
            );

        $blockend = pack("a1a100a6a2a32a32a8a8a155a12",
            $type,
            $link,
            "ustar",
            "00",
            "Unknown",
            "Unknown",
            "",
            "",
            $path,
            "");

        $checksum = 8*ord(" ");
        for ($i = 0; $i < 148; $i++) {
            $checksum += ord($blockbeg{$i});
        }
        for ($i = 0; $i < 356; $i++) {
            $checksum += ord($blockend{$i});
        }

        $checksum = pack("a8",sprintf("%6s ",decoct($checksum)));

        return $blockbeg . $checksum . $blockend;
    }
    /**
     * Creates the TAR footer for a file
     *
     * @param  array $stat the statistics of the file
     * @return string A string made of less than 512 characteres to fill the
     *         last 512 byte long block
     * @access private
     */
    function tarFooter($stat)
    {
        $size = $stat[7];
        if ($size % 512 > 0) {
            return pack("a".(512 - $size%512), "");
        } else {
            return "";
        }
    }

    /**
     * @see    File_Archive_Writer_MemoryArchive::appendFile()
     * @access protected
     */
    function appendFile($filename, $dataFilename)
    {
        $stat = stat($dataFilename);

        $error = $this->innerWriter->writeData(
                    $this->tarHeader($filename, $stat)
                 );
        if (PEAR::isError($error)) {
            return $error;
        }
        $error = $this->innerWriter->writeFile($dataFilename);
        if (PEAR::isError($error)) {
            return $error;
        }
        $error = $this->innerWriter->writeData(
                    $this->tarFooter($stat)
                 );
        if (PEAR::isError($error)) {
            return $error;
        }
    }
    /**
     * @see File_Archive_Writer_MemoryArchive::appendFileData()
     * @access protected
     */
    function appendFileData($filename, $stat, $data)
    {
        $size = strlen($data);
        $stat[7] = $size;

        $error = $this->innerWriter->writeData(
                    $this->tarHeader($filename, $stat)
                 );
        if (PEAR::isError($error)) {
            return $error;
        }
        $error = $this->innerWriter->writeData($data);
        if (PEAR::isError($error)) {
            return $error;
        }
        $error = $this->innerWriter->writeData(
                    $this->tarFooter($stat)
                 );
        if (PEAR::isError($error)) {
            return $error;
        }
    }
    /**
     * @see File_Archive_Writer_MemoryArchive::sendFooter()
     * @access protected
     */
    function sendFooter()
    {
        return $this->innerWriter->writeData(pack("a1024", ""));
    }
    /**
     * @see File_Archive_Writer::getMime()
     */
    function getMime() { return "application/x-tar"; }
}


/**
 * A tar archive cannot contain files with name of folders longer than 100 chars
 * This filter removes them
 *
 * @see File_Archive_Predicate, File_Archive_Reader_Filter
 */
require_once "File/Archive/Predicate.php";
class File_Archive_Predicate_TARCompatible extends File_Archive_Predicate
{
    function isTrue($source)
    {
        $pos = strrpos($source->getFilename(), "/");
        return ($pos<100) && (strlen($filename) - $pos < 155);
    }
}

?>