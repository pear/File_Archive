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

require_once "File/Archive/Reader/Archive.php";
require_once "File/Archive/Writer/Files.php";

/**
 * Uncompress a file that was compressed in the Gzip format
 */
class File_Archive_Reader_Gzip extends File_Archive_Reader_Archive
{
    var $nbRead = 0;
    var $filePos = 0;
    var $gzfile = null;
    var $tmpName = null;

    /**
     * @see File_Archive_Reader::close()
     */
    function close($innerClose = true)
    {
        if ($this->gzfile != null) {
            gzclose($this->gzfile);
        }
        if ($this->tmpName != null) {
            unlink($this->tmpName);
        }

        $this->nbRead = 0;
        $this->filePos = 0;
        $this->gzfile = null;
        $this->tmpName = null;

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
        $this->filePos = 0;
        if ($this->nbRead > 1) {
            return false;
        }

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
        $slashPos = strrpos($name, '/');
        if ($slashPos !== false) {
            $name = substr($name, $slashPos+1);
        }
        $dotPos = strrpos($name, '.');
        if ($dotPos !== false && $dotPos > 0) {
            $name = substr($name, 0, $dotPos);
        }

        return $name;
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
                $newData = gzread($this->gzfile, 8192);
                $data .= $newData;
            } while ($newData != '');
        } else if ($length == 0) {
            return '';
        } else {
            $data = gzread($this->gzfile, $length);
        }

        $this->filePos += strlen($data);
        return $data == '' ? null : $data;
    }

    /**
     * @see File_Archive_Reader::skip()
     */
    function skip($length)
    {
        if($length == -1) {
            do
            {
                $tmp = gzread($this->gzfile, 8192);
                $this->filePos += strlen($tmp);
            } while ($tmp != '');
        } else {
            if (@gzseek($this->gzfile, $this->filePos + $length) === -1) {
                return parent::skip($length);
            } else {
                $this->filePos += $length;
                return $length;
            }
        }
    }

    /**
     * @see File_Archive_Reader::rewind()
     */
    function rewind($length)
    {
        if ($length == -1) {
            if (@gzseek($this->gzfile, 0) === -1) {
                return parent::rewind($length);
            } else {
                $tmp = $this->filePos;
                $this->filePos = 0;
                return $tmp;
            }
        } else {
            $length = min($length, $this->filePos);
            if (@gzseek($this->gzfile, $this->filePos - $length) === -1) {
                return parent::rewind($length);
            } else {
                $this->filePos -= $length;
                return $length;
            }
        }
    }


    /**
     * @see File_Archive_Reader::makeWriter
     */
    function makeWriter($fileModif = true, $seek = 0)
    {
        require_once "File/Archive/Writer/Gzip.php";

        if ($fileModif == false) {
            return PEAR::raiseError(
                'A GZip archive contains one single file. '.
                'makeWriter must be called with $fileModif set to true'
            );
        }

        if ($this->nbRead == 0) {
            return new File_Archive_Writer_Gzip(
                null, $this->source->makeWriter()
            );
        } else {
            //Uncompress data to a temporary file
            $tmp = tmpfile();

            if ($this->nbRead == 1) {
                $toRead = gztell($this->gzfile) + $seek;

                gzseek($this->gzfile, 0);

                while ($toRead > 0 &&
                       ($data = gzread($this->gzfile, min($toRead, 8192))) != '') {
                    $toRead -= strlen($data);
                    fwrite($tmp, $data);
                }
            } else {
                gzseek($this->gzfile, 0);
                while (($data = $this->getData(8192)) !== null) {
                    fwrite($tmp, $data);
                }
                if ($seek < 0) {
                    echo "removing $seek from file\n";
                    ftruncate($tmp, ftell($tmp) + $seek);
                }
            }

            fseek($tmp, 0);

            //Create the writer
            $innerWriter = $this->source->makeWriter();
            $this->source = null;
            $writer = new File_Archive_Writer_Gzip(null, $innerWriter);

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