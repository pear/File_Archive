<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Uncompress a file that was compressed in the Bzip2 format
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
 * Uncompress a file that was compressed in the Bzip2 format
 */
class File_Archive_Reader_Bzip2 extends File_Archive_Reader_Archive
{
    var $data = null;
    var $offset = 0;
    var $alreadyRead = false;

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

        $this->data = $this->source->getData();
        if (PEAR::isError($this->data)) {
            return $this->data;
        }

        $this->data = bzdecompress($this->data);
        $this->offset = 0;

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