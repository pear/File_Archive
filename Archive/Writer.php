<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Base class for any writer
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

require_once "PEAR.php";

/**
 * Base class for any writer
 */
class File_Archive_Writer
{
    /**
     * Create a new file in the writer
     *
     * @param string $filename Name of the file, eventually including a path
     * @param array $stat Its Statistics. None of the indexes are required
     * @param string $mime MIME type of the file
     */
    function newFile($filename, $stat = array(), $mime = "application/octet-stream")
    {
        return PEAR::raiseError("Writer abstract function call (newFile)");
    }

    /**
     * Append the specified data to the writer
     *
     * @param String $data the data to append to the writer
     */
    function writeData($data)
    {
        return PEAR::raiseError("Writer abstract function call (writeData)");
    }

    /**
     * Append the content of the physical file $filename to the writer
     * writeFile($filename) must be equivalent to
     * writeData(file_get_contents($filename)) but can be more efficient
     *
     * @param string $filename Name of the file which content must be appended
     *        to the writer
     */
    function writeFile($filename)
    {
        $handle = fopen($filename, "r");
        if (!is_resource($handle)) {
            return PEAR::raiseError("Unable to write to $filename");
        }
        while (!feof($handle)) {
            $error = $this->writeData(fread($handle, 102400));
            if (PEAR::isError($error)) {
                return $error;
            }
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