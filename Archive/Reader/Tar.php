<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Read a tar archive
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
 * Read a tar archive
 */
class File_Archive_Reader_Tar extends File_Archive_Reader_Archive
{
    /**
     * @var String Name of the file being read
     * @access private
     */
    var $currentFilename = NULL;
    /**
     * @var Array Stats of the file being read
     *            In TAR reader, indexes 2, 4, 5, 7, 9 are set
     * @access private
     */
    var $currentStat = NULL;
    /**
     * @var Integer Number of bytes that still have to be read before the end of file
     * @access private
     */
    var $leftLength = 0;
    /**
     * @var Integer Size of the footer
     *              A TAR file is made of chunks of 512 bytes. If 512 does not divide
     *              the file size a footer is added
     * @access private
     */
    var $footerLength = 0;

    /**
     * @see File_Archive_Reader::skip()
     */
    function skip($length)
    {
        $actualLength = min($this->leftLength, $length);
        $this->source->skip($actualLength);
        $length -= $actualLength;
    }

    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        $this->leftLength = 0;
        $this->currentFilename = NULL;
        $this->currentStat = NULL;
        parent::close();
    }

    /**
     * @see File_Archive_Reader::getFilename()
     */
    function getFilename() { return $this->currentFilename; }
    /**
     * @see File_Archive_Reader::getStat()
     */
    function getStat() { return $this->currentStat; }

    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        if(!parent::next()) {
            return false;
        }

        $this->source->skip($this->leftLength + $this->footerLength);
        $rawHeader = $this->source->getData(512);
        if(PEAR::isError($rawHeader)) {
            return $rawHeader;
        }
        if(strlen($rawHeader)<512 || $rawHeader == pack("a512", "")) {
            return false;
        }

        $header = unpack(
            "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/".
            "a8checksum/a1type/a100linkname/a6magic/a2version/".
            "a32uname/a32gname/a8devmajor/a8devminor/a155path",
            $rawHeader);
        $this->currentStat = array(
            2 => octdec($header['mode']),
            4 => octdec($header['uid']),
            5 => octdec($header['gid']),
            7 => octdec($header['size']),
            9 => octdec($header['mtime'])
            );
        $this->currentFilename = $this->getStandardURL($header['path'].$header['filename']);

        $this->leftLength = $this->currentStat[7];
        if($this->leftLength % 512 == 0) {
            $this->footerLength = 0;
        } else {
            $this->footerLength = 512 - $this->leftLength%512;
        }

        $checksum = 8*ord(" ");
        for($i = 0; $i < 148; $i++) {
            $checksum += ord($rawHeader{$i});
        }
        for($i = 156; $i < 512; $i++) {
            $checksum += ord($rawHeader{$i});
        }

        if(octdec($header['checksum']) != $checksum) {
            die('Checksum error on entry '.$this->currentFilename);
        }

        return true;
    }
    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if($length == -1) {
            $actualLength = $this->leftLength;
        } else {
            $actualLength = min($this->leftLength, $length);
        }

        if($this->leftLength == 0) {
            return NULL;
        } else {
            $data = $this->source->getData($actualLength);
            $this->leftLength -= $actualLength;
            return $data;
        }
    }
}

?>