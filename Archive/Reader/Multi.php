<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Regroups several readers to make them appear as a single one
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

require_once "Relay.php";

/**
 * Regroups several readers to make them appear as a single one
 */
class File_Archive_Reader_Multi extends File_Archive_Reader_Relay
{
    /**
     * @var Array All the sources regrouped in this reader
     * @access private
     */
    var $sources = array();
    /**
     * @var Int Index of the source being read currently
     * @access private
     */
    var $currentIndex = 0;

    /**
     * Add a new reader to the list of readers
     */
    function addSource(&$source)
    {
        $this->sources[] =& $source;
    }

    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        while(array_key_exists($this->currentIndex, $this->sources))
        {
            $this->source =& $this->sources[$this->currentIndex];

            if($this->source->next()) {
                return true;
            } else {
                $this->source->close();
                $this->currentIndex++;
            }
        }
        return false;
    }
    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        parent::close();
        $this->currentIndex = 0;
    }
}

?>