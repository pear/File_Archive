<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ZIP archive reader
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
 * ZIP archive reader
 * Currently only allows to browse the archive (getData is not available)
 */
class File_Archive_Reader_Zip extends File_Archive_Reader_Archive
{
    var $currentFilename = null;
    var $currentStat = null;
    var $header = null;
    var $offset = 0;
    var $data = null;

    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        $this->currentFilename = null;
        $this->currentStat = null;
        $this->compLength = 0;
        $this->data = null;

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

        //Skip the data and the footer if they haven't been uncompressed
        if($this->header != null && $this->data == null) {
            $toSkip = $this->header['CLen'];
            $this->source->skip($toSkip);
        }

        $this->offset = 0;
        $this->data = null;

        //Read the header
        if($this->source->getData(4) == "\x50\x4b\x03\x04") {
            //New entry
            $this->header = unpack(
                "vVersion/vFlag/vMethod/vTime/vDate/VCRC/VCLen/VNLen/vFile/vExtra",
                $this->source->getData(26));

            //Check the compression method
            if($this->header['Method'] != 0 &&
               $this->header['Method'] != 8) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't handle compression method {$this->header['Method']}");
            }
            if($this->header['Flag'] & 1) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't handle encrypted files");
            }
            if($this->header['Flag'] & 8) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't handle bit flag 3 set");
            }
            if($this->header['Flag'] & 32) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't handle compressed patched data");
            }
            if($this->header['Flag'] & 64) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't handle strong encrypted files");
            }

            //TODO: put time / date in stats
            $this->currentStat = array(7=>$this->header['NLen']);

            $this->currentFilename = $this->source->getData($this->header['File']);

            $this->source->skip($this->header['Extra']);

            return true;
        } else {
            //Begining of central area
            return false;
        }
    }
    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if($this->offset >= $this->currentStat[7]) {
            return null;
        }

        if($length>=0) {
            $actualLength = min($length, $this->currentStat[7]-$this->offset);
        } else {
            $actualLength = $this->currentStat[7]-$this->offset;
        }

        $error = $this->uncompressData();
        if(PEAR::isError($error)) {
            return $error;
        }
        $result = substr($this->data, $this->offset, $actualLength);
        $this->offset += $actualLength;
        return $result;
    }
    /**
     * @see File_Archive_Reader::skip()
     */
    function skip($length)
    {
        $this->offset = min($this->offset + $length, $this->currentStat[7]);
    }
    function uncompressData()
    {
        if($this->data !== NULL)
            return;

        $this->data = $this->source->getData($this->header['CLen']);
        if($this->header['Method'] == 8) {
            $this->data = gzinflate($this->data);
        }

        if(crc32($this->data) != $this->header['CRC']) {
            return PEAR::raiseError("Zip archive : CRC fails on entry {$this->currentFilename}");
        }
    }
}
?>