<?
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Keep only one precise file
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

require_once "File/Archive/Predicate.php";

/**
 * Keep only one precise file
 */
class File_Archive_Predicate_Select extends File_Archive_Predicate
{
    var $filename;

    function File_Archive_Predicate_Select($filename)
    {
        $this->filename = $filename;
        if(substr($this->filename, -1) == '/') {
            $this->filename = substr($this->filename, 0, -1);
        }
    }

    /**
     * @see File_Archive_Predicate::isTrue()
     */
    function isTrue(&$source)
    {
        $sourceName = $source->getFilename();

        return  empty($this->filename) ||

                //$this->filename is a file
                $this->filename == $sourceName ||

                //$this->filename is a directory
                strncmp($this->filename.'/', $sourceName, strlen($this->filename)+1) == 0;
    }
}

?>