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

require_once "File/Archive/Writer.php";

/**
  * Write the concatenation of the files
  * in a buffer
  */
class File_Archive_Writer_Memory extends File_Archive_Writer
{
    /**
      * @var string $data The buffer
      */
    var $data = "";

    function writeData($d) { $this->data .= $d; }

    /**
      * Retrieve the concatenated data
      *
      * @return string buffer
      */
    function getData() { return $this->data; }

    /**
      * Clear the buffer
      */
    function clear() { $this->data = ""; }

    /**
      * Returns true iif the buffer is empty
      */
    function isEmpty() { return empty($this->data); }
}

?>