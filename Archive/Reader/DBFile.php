<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This reader will display the files contained in an SQL request
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

require_once "File/Archive.php";
require_once "Relay.php";
require_once "DB.php";

/**
 * This reader will display the files contained in an SQL request
 * This reader does not free the given ressource
 * It is your responsibility to call $res->free() if necessary
 *
 * The ressource is the result of an SQL request that have one or two columns
 *  Column 0: URL of the stream (this will be given to File_Archive::read)
 *  Column 1: Symbolic name of the URL (default: column 0)
 */
class File_Archive_Reader_DBFile extends File_Archive_Reader_Relay
{
    /**
     * @var Object Handle to the set of results being processed
     *             The rows must contain an URL in first pos and optionally
     *              a public name in second pos
     * @access private
     */
    var $res = null;

    /**
     * @var Integer Current row number
     * @access private
     */
    var $currentRowPos = 0;

    /**
     * @param Object $res Handle to the set of results being processed
     *               The rows must contain an URL in first pos and optionally
     *                a public name in second pos
     * @see DB::query
     */
    function File_Archive_Reader_DBFile($res)
    {
        $this->res = $res;
    }
    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        parent::close();

        $this->currentRow = 0;
        $this->source = null;
    }
    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        $row = $this->res->fetchRow(DB_FETCHMODE_ORDERED, $this->currentRowPos);
        if($row == null) {
            //This was the last row
            return false;
        } else {
            //Close the current source, if it was opened
            parent::close();

            $this->currentRowPos++;

            if(!isset($row[1])) {
                //Default value for symbolic representation: URL
                $row[1] = $row[0];
            }

            $source = File_Archive::read($row[0], $row[1]);

            if(PEAR::isError($source)) {
                return $source;
            } else {
                $this->source = $source;
                return true;
            }
        }

    }
}

?>