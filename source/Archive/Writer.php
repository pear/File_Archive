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

require_once "PEAR.php";

/**
  * Base class for any writer
  */
class File_Archive_Writer
{
    /**
      * Create a new file in the writer
      *
      * @param String $data filename the name of the file, eventually including a path
      * @stat Array $stat See PHP stat() function. None of the indexed are required
      */
    function newFile($filename, $stat, $mime = "application/octet-stream")
    {
        return PEAR::raiseError("Abstract function call");
    }

    /**
      * Append the specified data to the writer
      *
      * @param String $data the data to append to the writer
      */
    function writeData($data)
    {
        return PEAR::raiseError("Abstract function call");
    }

    /**
      * Append the content of the physical file $filename to the writer
      * writeFile($filename) must be equivalent to writeData(file_get_contents($filename)) but can be more efficient
      *
      * @param String $filename Name of the file which content must be appended to the writer
      */
    function writeFile($filename)
    {
        $handle = fopen($filename, "r");
        while(!feof($handle))
        {
            $this->writeData(fread($handle, 102400));
        }
        fclose($handle);
    }

    /**
      * Close the writer, eventually flush the data, write the footer...
      * This function must be called before the end of the script
      */
    function close() { }
}

?>