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

require_once "Relay.php";
require_once "File.php";

/**
 * Recursively reads a directory
 */
class File_Archive_Reader_Directory extends File_Archive_Reader_Relay
{
    /**
     * @var String URL of the directory that must be read
     */
    var $directory;
    /**
     * @var Int The subdirectories will be read up to a depth of maxRecurs
     * If maxRecurs == 0, the subdirectories will not be read
     * If maxRecurs == -1, the depth is considered infinite
     */
    var $maxRecurs;
    /**
     * @var Object Handle returned by the openedDirectory function
     */
    var $directoryHandle = null;

    /**
     * $directory is the path of the directory that must be read
     * If $maxRecurs is specified, the subdirectories will be read up to a depth of $maxRecurs
     * In particular, if $maxRecurs == 0, the subdirectories won't be read.
     */
    function File_Archive_Reader_Directory($directory, $symbolic='', $maxRecurs=-1)
    {
        $this->directory = $directory;
        $this->symbolic = $this->getStandardURL($symbolic);
        $this->maxRecurs = $maxRecurs;
    }
    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        parent::close();

        $this->directoryHandle = null;
        $this->source = null;
    }
    /**
     * @see File_Archive_Reader::next()
     *
     * The files are returned in the same order as readdir
     */
    function next()
    {
        if($this->directoryHandle == null)
            $this->directoryHandle = opendir($this->directory);

        while($this->source == null ||
             !$this->source->next())
        {
            $file = readdir($this->directoryHandle);
            if($file == '.' || $file == '..') {
                continue;
            }
            if($file === false) {
                return false;
            }

            $current = $this->directory.'/'.$file;
            if(is_dir($current)) {
                if($this->maxRecurs != 0)
                    $this->source = new File_Archive_Reader_Directory($current, $file.'/', $this->maxRecurs-1);
            } else {
                $this->source = new File_Archive_Reader_File($current, $file);
            }
        }

        return true;
    }
    /**
     * @see File_Archive_Reader::getFilename()
     */
    function getFilename() { return $this->symbolic . parent::getFilename(); }
}

?>