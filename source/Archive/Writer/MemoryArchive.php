<?
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: Vincent Lascaux <vincent.lascaux at centraliens.net>        |
// +----------------------------------------------------------------------+
//
// $Id$

require_once "Archive.php";
require_once "Memory.php";

/**
  * Base class for all the archiveWriters that can only work on complete files
  * (the write data function may be called with small chunks of data)
  */
class File_Archive_Writer_MemoryArchive extends File_Archive_Writer_Archive
{
    /**
      * @var File_Archive_Writer_Memory A buffer where the data will be put waiting for the file to be complete
      */
    var $memoryWriter = null;
    /**
      * @var String Name of the file which data are coming
      */
    var $currentFilename = null;
    /**
      * @var Array Stats of the file which data are coming
      */
    var $currentStat = null;
    /**
      * @var String URL of the file being treated if it is a physical file
      */
    var $currentDataFile = null;
    /**
      * @var Int Number of times newFile function has been called
      */
    var $nbFiles = 0;

    /**
      * See the constructor of File_Archive_Writer for more informations
      */
    function File_Archive_Writer_MemoryArchive($filename, &$t, $stat=array(), $autoClose = true)
    {
        $this->memoryWriter = new File_Archive_Writer_Memory();
        parent::File_Archive_Writer_Archive($filename, $t, $stat, $autoClose);
    }
    function newFile($filename, $stat, $mime = "application/octet-stream")
    {
        if($this->nbFiles == 0) {
            $this->sendHeader();
        } else {
            $this->flush();
        }

        $this->nbFiles++;

        $this->currentFilename = $filename;
        $this->currentStat = $stat;

        return true;
    }
    function close()
    {
        $this->flush();
        $this->sendFooter();

        parent::close();
    }
    /**
      * Indicate that all the data have been read from the current file
      * and send it to appendFileData
      * Send the current data to the appendFileData function
      */
    function flush()
    {
        if($this->currentFilename !== null) {
            if($this->currentDataFile !== null)
                $this->appendFile($this->currentFilename,
                                  $this->currentDataFile);
            else
                $this->appendFileData($this->currentFilename,
                                 $this->currentStat,
                                 $this->memoryWriter->getData());

            $this->currentFilename = null;
            $this->currentDataFile = null;
            $this->memoryWriter->clear();
        }
    }
    function writeData($data) { $this->memoryWriter->writeData($data); }
    function writeFile($filename)
    {
        if($this->currentDataFile == null && $this->memoryWriter->isEmpty()) {
            $this->currentDataFile = $filename;
        } else {
            $this->memoryWriter->writeFile($filename);
        }
    }

//MUST REWRITE FUNCTIONS
    /**
      * The subclass must treat the data $data
      * $data is the entire data of the filename $filename
      * $stat is the stat of the file
      */
    function appendFileData($filename, $stat, $data) { }

//SHOULD REWRITE FUNCTIONS
    /**
      * The subclass may rewrite the sendHeader function if it needs to execute code
      * before the first file
      */
    function sendHeader() { }
    /**
      * The subclass may rewrite the sendFooter function if it needs to execute code
      * before closing the archive
      */
    function sendFooter() { }
    /**
      * The subclass may rewrite this class if it knows an efficient way to treat a physical file
      * This function is equivalent to $this->appendFileData($filename, stat($dataFilename), file_get_contents($dataFilename));
      * but may be more efficient
      */
    function appendFile($filename, $dataFilename)
    {
        $this->appendFileData($filename, stat($dataFilename), file_get_contents($dataFilename));
    }
}

?>