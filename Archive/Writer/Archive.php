<?
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
 * Base class for all the transformation writers that will generate one single file
 */
class File_Archive_Writer_Archive extends File_Archive_Writer
{
    /**
     * @var File_Archive_Writer The compressed data will be written to this writer
     * @access private
     */
    var $innerWriter = NULL;

    /**
     * @var bool If true, the innerWriter will be closed when closing this
     * @access private
     */
    var $autoClose = TRUE;

    /**
     * @param String $filename Name to give to the archive (the name will probably be used by the inner writer)
     * @param File_Archive_Writer $innerWriter The inner writer to which the compressed data will be written
     * @param Array $stat The stat of the archive (see the PHP stat() function). No element are required in this array
     * @param Bool $autoClose Indicate if the inner writer must be closed when closing this
     */
    function File_Archive_Writer_Archive($filename, &$innerWriter, $stat=array(), $autoClose = TRUE)
    {
        $this->innerWriter =& $innerWriter;
        $this->autoClose = $autoClose;
        $this->innerWriter->newFile($filename, $stat, $this->getMime());
    }

//MUST REWRITE FUNCTIONS
    //function newFile($filename, $stat, $mime) { }

    /**
     * @return the MIME extension of the files generated by this writer
     */
    function getMime() { return "application/octet-stream"; }

    /**
     * @see File_Archive_Writer::close
     */
    function close()
    {
        if($this->autoClose) {
            $this->innerWriter->close();
        }
    }
//  function writeData($data)

//SHOULD REWRITE FUNCTIONS
//  function writeFile($filename)
}

?>