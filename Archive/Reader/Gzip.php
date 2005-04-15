<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Uncompress a file that was compressed in the Gzip format
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
 * Uncompress a file that was compressed in the Gzip format
 */
class File_Archive_Reader_Gzip extends File_Archive_Reader_Archive
{
    var $data = null;
    var $offset = 0;
    var $alreadyRead = false;

    var $comment = null;
    var $hasName = false;
    var $hasComment = false;

    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        $this->data = null;
        $this->offset = 0;
        $this->alreadyRead = false;
        return parent::close();
    }

    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        if (!parent::next()) {
            return false;
        }

        if ($this->alreadyRead) {
            return false;
        }
        $this->alreadyRead = true;

        $header = $this->source->getData(10);
        if (PEAR::isError($header)) {
            return $header;
        }

        $id = unpack("H2id1/H2id2/C1tmp/C1flags",substr($header,0,4));
        if ($id['id1'] != "1f" || $id['id2'] != "8b") {
            return PEAR::raiseError("Not valid gz file (wrong header)");
        }

        $temp = decbin($id['flags']);
        $this->hasComment = ($temp & 0x4);

        $this->comment = "";
        if ($this->hasComment) {
            while (($char = $this->source->getData(1)) !== "\0") {
                if ($char === null) {
                    return PEAR::raiseError(
                        "Not valid gz file (unexpected end of archive ".
                        "reading comment)"
                    );
                }
                if (PEAR::isError($char)) {
                    return $char;
                }
                $this->comment .= $char;
            }
        }

        $this->data = $this->source->getData();
        if (PEAR::isError($this->data)) {
            return $this->data;
        }

        $temp = unpack("Vcrc32/Visize",substr($this->data,-8));
        $crc32 = $temp['crc32'];
        $size = $temp['isize'];

        $this->data = gzinflate(substr($this->data,0,strlen($this->data)-8));
        $this->offset = 0;

        if ($size != strlen($this->data)) {
            return PEAR::raiseError(
                "Not valid gz file (size error {$size} != ".
                strlen($this->data).")"
            );
        }
        if ($crc32 != crc32($this->data)) {
            return PEAR::raiseError("Not valid gz file (checksum error)");
        }
        return true;
    }

    /**
     * Return the name of the single file contained in the archive
     * deduced from the name of the archive (the extension is removed)
     *
     * @see File_Archive_Reader::getFilename()
     */
    function getFilename()
    {
        $name = $this->source->getFilename();
        $pos = strrpos($name, ".");
        if ($pos === false || $pos === 0) {
            return $name;
        } else {
            return substr($name, 0, $pos);
        }
    }

    /**
     * @see File_Archive_Reader::getStat()
     */
    function getStat()
    {
        return array(
            7 => strlen($this->data)
        );
    }
    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if ($length == -1) {
            $actualLength = strlen($this->data) - $this->offset;
        } else {
            $actualLength = min(strlen($this->data) - $this->offset, $length);
        }

        if ($actualLength == 0) {
            return null;
        } else {
            $result = substr($this->data, $this->offset, $actualLength);
            $this->offset += $actualLength;
            return $result;
        }
    }
    /**
     * @see File_Archive_Reader::skip()
     */
    function skip($length)
    {
        $this->offset += $length;
        return $length;
    }
}

?>