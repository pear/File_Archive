<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ZIP archive reader
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

/**
 * ZIP archive reader
 * Currently only allows to browse the archive (getData is not available)
 */
class File_Archive_Reader_Zip extends File_Archive_Reader_Archive
{
    var $currentFilename = null;
    var $currentStat = null;
    var $header = null;
    var $offset = 0;
    var $data = null;
    var $files = array();
    var $seekToEnd = 0;

    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        $this->currentFilename = null;
        $this->currentStat = null;
        $this->compLength = 0;
        $this->data = null;
        $this->seekToEnd = 0;
        $this->files = array();

        return parent::close();
    }

    /**
     * @see File_Archive_Reader::getFilename()
     */
    function getFilename() { return $this->currentFilename; }
    /**
     * @see File_Archive_Reader::getStat()
     */
    function getStat() { return $this->currentStat; }

    /**
     * Go to next entry in ZIP archive
     * This function may stop on a folder, so it does not comply to the
     * File_Archive_Reader::next specs
     *
     * @see File_Archive_Reader::next()
     */
    function nextWithFolders()
    {
        if ($this->seekToEnd > 0) {
            return false;
        }

        //Skip the data and the footer if they haven't been uncompressed
        if ($this->header != null && $this->data == null) {
            $toSkip = $this->header['CLen'];
            $error = $this->source->skip($toSkip);
            if (PEAR::isError($error)) {
                return $error;
            }
        }

        $this->offset = 0;
        $this->data = null;

        //Read the header
        $header = $this->source->getData(4);
        if (PEAR::isError($header)) {
            return $header;
        }
        if ($header == "\x50\x4b\x03\x04") {
            //New entry
            $header = $this->source->getData(26);
            if (PEAR::isError($header)) {
                return $header;
            }
            $this->header = unpack(
                "vVersion/vFlag/vMethod/vTime/vDate/VCRC/VCLen/VNLen/vFile/vExtra",
                $header);

            //Check the compression method
            if ($this->header['Method'] != 0 &&
               $this->header['Method'] != 8) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't ".
                        "handle compression method {$this->header['Method']}");
            }
            if ($this->header['Flag'] & 1) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't ".
                        "handle encrypted files");
            }
            if ($this->header['Flag'] & 8) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't ".
                        "handle bit flag 3 set");
            }
            if ($this->header['Flag'] & 32) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't ".
                        "handle compressed patched data");
            }
            if ($this->header['Flag'] & 64) {
                return PEAR::raiseError("File_Archive_Reader_Zip doesn't ".
                        "handle strong encrypted files");
            }

            $this->currentStat = array(
                7=>$this->header['NLen'],
                9=>mktime(
                    ($this->header['Time'] & 0xF800) >> 11,         //hour
                    ($this->header['Time'] & 0x07E0) >> 5,          //minute
                    ($this->header['Time'] & 0x001F) >> 1,          //second
                    ($this->header['Date'] & 0x01E0) >> 5,          //month
                    ($this->header['Date'] & 0x001F)     ,          //day
                   (($this->header['Date'] & 0xFE00) >> 9) + 1980   //year
                )
            );
            $this->currentStat['size']  = $this->currentStat[7];
            $this->currentStat['mtime'] = $this->currentStat[9];

            $this->currentFilename = $this->source->getData($this->header['File']);

            $error = $this->source->skip($this->header['Extra']);
            if (PEAR::isError($error)) {
                return $error;
            }

            $this->files[] = array('name' => $this->currentFilename,
                            'stat' => $this->currentStat,
                            'CRC' => $this->header['CRC'],
                            'CLen' => $this->header['CLen']
                           );

            return true;
        } else {
            //Begining of central area
            $this->seekToEnd = 4;
            return false;
        }
    }
    /**
     * Go to next file entry in ZIP archive
     * This function will not stop on a folder entry
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        if (!parent::next()) {
            return false;
        }

        do {
            $result = $this->nextWithFolders();
            if ($result !== true) {
                return $result;
            }
        } while (substr($this->getFilename(), -1) == '/');

        return true;
    }

    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if ($this->offset >= $this->currentStat[7]) {
            return null;
        }

        if ($length>=0) {
            $actualLength = min($length, $this->currentStat[7]-$this->offset);
        } else {
            $actualLength = $this->currentStat[7]-$this->offset;
        }

        $error = $this->uncompressData();
        if (PEAR::isError($error)) {
            return $error;
        }
        $result = substr($this->data, $this->offset, $actualLength);
        $this->offset += $actualLength;
        return $result;
    }
    /**
     * @see File_Archive_Reader::skip()
     */
    function skip($length)
    {
        $this->offset = min($this->offset + $length, $this->currentStat[7]);
    }
    function uncompressData()
    {
        if ($this->data !== null)
            return;

        $this->data = $this->source->getData($this->header['CLen']);
        if (PEAR::isError($this->data)) {
            return $this->data;
        }
        if ($this->header['Method'] == 8) {
            $this->data = gzinflate($this->data);
        }

        if (crc32($this->data) != $this->header['CRC']) {
            return PEAR::raiseError("Zip archive: CRC fails on entry ".
                                    $this->currentFilename);
        }
    }

    /**
     * @see File_Archive_Reader::makeWriter
     */
    function makeWriter($fileModif = true, $seek = 0)
    {
        if ($fileModif) {
            //TODO: inner modification of archives
            return PEAR::raiseError('Modification of nested archives not available');
        }

        require_once "File/Archive/Writer/Zip.php";

        if ($this->currentFilename == null) {
            //The zip file was not even opened
            $writer = new File_Archive_Writer_Zip(null, $this->source->makeWriter());
        } else {
            if ($this->seekToEnd == 0) {
                $seekToEnd = 26 + $this->header['File'] + $this->header['Extra'] + ($this->data == null ? 0 : $this->header['CRC']);
            } else {
                $seekToEnd = $this->seekToEnd;
            }

            $writer = new File_Archive_Writer_Zip(null, $this->source->makeWriter(true, - $seekToEnd));

            if (!empty($this->files) && $this->seekToEnd == 0) {
                //Last file will be rewritten
                array_pop($this->files);
            }
            foreach ($this->files as $file) {
                $writer->alreadyWrittenFile($file['name'], $file['stat'], $file['CRC'], $file['CLen']);
            }

            if ($this->seekToEnd == 0) {
                $writer->newFile($this->getFilename(),
                                 $this->getStat(),
                                 $this->getMime());
                $writer->writeData($this->data == null ? '' : substr($this->data, 0, $this->offset + $seek));
            }
        }

        $this->source = null;
        $this->close();
        return $writer;
    }
}
?>