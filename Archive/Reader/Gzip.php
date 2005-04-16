<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Uncompress a file that was compressed in the Gzip format
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
require_once "File/Archive/Writer/Files.php";

/**
 * Uncompress a file that was compressed in the Gzip format
 */
class File_Archive_Reader_Gzip extends File_Archive_Reader_Archive
{
    var $alreadyRead = false;
    var $gzfile = null;
    var $tmpName = null;

    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        if($this->gzfile != null)
            gzclose($this->gzfile);
        if($this->tmpName != null)
            unlink($this->tmpName);

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

        $dataFilename = $this->source->getDataFilename();
        if ($dataFilename != null)
        {
            $this->tmpName = null;
            $this->gzfile = gzopen($dataFilename, 'r');
        } else {
            $this->tmpName = tempnam('.', 'far');

            //Generate the tmp data
            $dest = new File_Archive_Writer_Files();
            $dest->newFile($this->tmpName);
            $this->source->sendData($dest);
            $dest->close();

            $this->gzfile = gzopen($this->tmpName, 'r');
        }

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
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if ($length == -1) {
            $data = '';
            do
            {
                $newData = gzread($this->gzfile, 8196);
                $data .= $newData;
            } while($newData != '');
        } else if ($length == 0) {
            return '';
        } else {
            $data = gzread($this->gzfile, $length);
        }

        return $data == '' ? null : $data;
    }
}

?>