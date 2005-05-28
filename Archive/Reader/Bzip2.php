<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Uncompress a file that was compressed in the Bzip2 format
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

require_once "File/Archive/Writer/Archive.php";
require_once "File/Archive/Writer/Files.php";

/**
 * Uncompress a file that was compressed in the Bzip2 format
 */
class File_Archive_Reader_Bzip2 extends File_Archive_Reader_Archive
{
    var $nbRead = 0;
    var $bzfile = null;
    var $tmpName = null;
    var $pos = 0;

    /**
     * @see File_Archive_Reader::close()
     */
    function close($innerClose = true)
    {
        if ($this->bzfile != null)
            bzclose($this->bzfile);
        if ($this->tmpName != null)
            unlink($this->tmpName);

        $this->nbRead = 0;
        $this->pos = 0;
        return parent::close($innerClose);
    }

    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        if (!parent::next()) {
            return false;
        }

        $this->nbRead++;
        if ($this->nbRead > 1) {
            return false;
        }

        $dataFilename = $this->source->getDataFilename();
        if ($dataFilename != null)
        {
            $this->tmpName = null;
            $this->bzfile = bzopen($dataFilename, 'r');
        } else {
            $this->tmpName = tempnam('.', 'far');

            //Generate the tmp data
            $dest = new File_Archive_Writer_Files();
            $dest->newFile($this->tmpName);
            $this->source->sendData($dest);
            $dest->close();

            $this->bzfile = bzopen($this->tmpName, 'r');
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
            do {
                $newData = bzread($this->bzfile);
                $data .= $newData;
            } while ($newData != '');
            $this->pos += strlen($data);
        } else if ($length == 0) {
            return '';
        } else {
            $data = '';

            //The loop is here to correct what appears to be a bzread bug
            while (strlen($data) < $length) {
                $data .= bzread($this->bzfile, $length - strlen($data));
            }
            $this->pos += strlen($data);
        }

        return $data == '' ? null : $data;
    }

    /**
     * @see File_Archive_Reader::rewind
     */
    function rewind($length)
    {
        //TODO: implement rewind
        return parent::rewind($length);
    }

    /**
     * @see File_Archive_Reader::makeWriter
     */
    function makeWriter($fileModif = true, $seek = 0)
    {
        require_once "File/Archive/Writer/Bzip2.php";

        if ($fileModif == false) {
            return PEAR::raiseError(
                'A Bzip archive contains one single file. '.
                'makeWriter must be called with $fileModif set to true'
            );
        }

        if ($this->nbRead == 0) {
            return new File_Archive_Writer_Bzip2(
                null, $this->source->makeWriter()
            );
        } else {
            //Uncompress data to a temporary file
            $tmp = tmpfile();

            $toRead = $this->pos + $seek;

            //bzseek($this->bzfile, 0);
            bzclose($this->bzfile);
            if ($this->tmpName == null) {
                $this->bzfile = bzopen($this->source->getDataFilename(), 'r');
            } else {
                $this->bzfile = bzopen($this->tmpName, 'r');
            }


            while ($toRead > 0 &&
                   ($data = bzread($this->bzfile, min($toRead, 8192))) != '') {
                $toRead -= strlen($data);
                fwrite($tmp, $data);
            }
            fseek($tmp, 0);

            //Create the writer
            $innerWriter = $this->source->makeWriter();
            $this->source = null;
            $writer = new File_Archive_Writer_Bzip2(null, $innerWriter);

            //And compress data from the temporary file
            while (!feof($tmp)) {
                $data = fread($tmp, 8192);
                $writer->writeData($data);
            }
            fclose($tmp);

            //Do not close inner writer since makeWriter was called
            $this->close();

            return $writer;
        }
    }

}

?>