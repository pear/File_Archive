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

require_once "Archive.php";

/**
  * ZIP archive reader
  * Currently only allows to browse the archive (getData is not available)
  */
class File_Archive_Reader_Zip extends File_Archive_Reader_Archive
{
    var $currentFilename = NULL;
    var $currentStat = NULL;
    var $compLength = 0;
    var $offset = 0;
    var $data = NULL;

    /**
      * @see File_Archive_Reader::close()
      */
    function close()
    {
        $this->currentFilename = NULL;
        $this->currentStat = NULL;
        $this->compLength = 0;
        $this->data = NULL;

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

        //Skip the data if they haven't been uncompressed and the footer
        if($this->currentStat != NULL && $this->data == NULL)
            $this->source->skip($this->compLength);
        else if($this->currentStat != NULL)
            $this->source->skip(0);

        //Read the header
        $header = $this->source->getData(30);
        switch(substr($header, 0, 4))
        {
        case "\x50\x4b\x03\x04":
            //New entry
            //TODO: read time
            $time = substr($header, 10, 4);
            $temp = unpack("VCRC/VCLen/VNLen/vfile", substr($header, 14));

            $this->compLength = $temp['CLen'];
            $this->currentStat = array(7=>$temp['NLen']);
            $this->currentFilename = $this->source->getData($temp['file']);
            $this->offset = 0;
            $this->data = NULL;
            return TRUE;
        case "\x50\x4b\x01\x02":
            //Begining of central area
            return FALSE;
        default:
            die("Not valid zip file");
        }
    }
    /**
      * @see File_Archive_Reader::getData()
      */
    function getData($length = -1)
    {
        if($this->offset >= $this->currentStat[7])
            return NULL;

        if($length>=0)
            $actualLength = min($length, $this->currentStat[7]-$this->offset);
        else
            $actualLength = $this->currentStat[7]-$this->offset;

        die("Function ZIP::getData not available in this version (extraction of ZIP archive not yet implemented)");
/*        $this->uncompressData();
        $result = substr($this->data, $this->offset, $actualLength);
        $this->offset += $actualLength;
        return $result;*/
    }
    /**
      * @see File_Archive_Reader::skip()
      */
    function skip($length)
    {
        $this->offset = min($this->offset + $length, $this->currentStat[7]);
    }

}
?>