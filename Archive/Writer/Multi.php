<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Write to several writers
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

require_once "File/Archive/Writer.php";

/**
 * Write to several writers
 */
//TODO: check that it's working with PHP4 (worried about the references)
class File_Archive_Writer_Multi extends File_Archive_Writer
{
    /**
     * @var File_Archive_Writer_Writer Data will be copied to these two writers
     * @access private
     */
    var $a, $b;

    function File_Archive_Writer_Multi(&$a, &$b)
    {
        $this->a =& $a;
        $this->b =& $b;
    }

    /**
     * @see File_Archive_Writer::newFile()
     */
    function newFile($filename, $stat = array(), $mime = "application/octet-stream")
    {
        $errorA = $this->a->newFile($filename, $stat, $mime);
        $errorB = $this->b->newFile($filename, $stat, $mime);

        if (PEAR::isError($errorA)) {
            return $errorA;
        }
        if (PEAR::isError($errorB)) {
            return $errorB;
        }
    }

    /**
     * @see File_Archive_Writer::writeData()
     */
    function writeData($data)
    {
        $errorA = $this->a->writeData($data);
        $errorB = $this->b->writeData($data);

        if (PEAR::isError($errorA)) {
            return $errorA;
        }
        if (PEAR::isError($errorB)) {
            return $errorB;
        }
    }

    /**
     * @see File_Archive_Writer::writeFile()
     */
    function writeFile($filename)
    {
        $errorA = $this->a->writeFile($filename);
        $errorB = $this->b->writeFile($filename);

        if (PEAR::isError($errorA)) {
            return $errorA;
        }
        if (PEAR::isError($errorB)) {
            return $errorB;
        }
    }

    /**
     * @see File_Archive_Writer::close()
     */
    function close()
    {
        $errorA = $this->a->close();
        $errorB = $this->b->close();

        if (PEAR::isError($errorA)) {
            return $errorA;
        }
        if (PEAR::isError($errorB)) {
            return $errorB;
        }
    }
}
?>