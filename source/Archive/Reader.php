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

require_once "PEAR.php";

/**
  * Abstract base class for all the readers
  *
  * A reader is a compilation of serveral files that can be read
  */
class File_Archive_Reader
{
    /**
      * Move to the next file in the reader
      *
      * @return boolean true iif no more files are available
      */
    function next()
    {
        return PEAR::raiseError("Abstract function call");
    }

    /**
      * Move to the next file whose name is $filename
      *
      * @param String $filename Name of the file to find in the archive
      * @return Bool whether the file was found in the archive or not
      */
    function select($filename)
    {
        $std = $this->getStandardURL($filename);

        //TODO: not very efficient: close and re open the archive to start from the begining
        $this->close();
        while($this->next())
        {
            if($this->getFilename() == $std)
                return true;
        }
        return false;
    }

    /**
      * Returns the standard path
      * Changes \ to /
      * Removes the .. and . from the URL
      */
    function getStandardURL($path)
    {
        $std = str_replace("\\", "/", $path);
        while($std != ($std = preg_replace("/[^\/:?]+\/\.\.\//", "", $std))) ;
        $std = str_replace("/./", "", $std);
        if(strncmp($std, "./", 2) == 0)
            return substr($std, 2);
        else
            return $std;
    }

    /**
      * Returns the name of the file currently read by the reader
      *
      * Warning: undefined behaviour if no call to next have been
      * done or if last call to next has returned false
      *
      * @return String Name of the current file
      */
    function getFilename() { return PEAR::raiseError("Abstract function call"); }

    /**
      * Returns an array of statistics about the file
      * (see the PHP stat function for more information)
      *
      * The only element that must be present in the array
      * is the size (index 7)
      * All the other element may not be present if the reader
      * doesnt know about it
      */
    function getStat() { return array(); }

    /**
      * Returns the MIME associated with the current file
      * The default function does that by looking at the extension of the file
      */
    function getMime()
    {
        require_once "Reader/MimeList.php";
        return File_Archive_Reader_GetMime($this->getFilename());
    }

    /**
      * If the current file of the archive is a physical file,
      * returns the name of this file (this can then be used in
      * a more efficient way than calling the reader s functions)
      * Else returns NULL
      *
      * The data filename may not be the same as the filename.
      */
    function getDataFilename() { return null; }

    /**
      * Reads some data from the current file
      * If the end of the file is reached, returns null
      * If $length is not specified, reads up to the end of the file
      * If $length is specified reads up to $length
      */
    function getData($length = -1) { return PEAR::raiseError("Abstract function call"); }

    /**
      * Skip some data and returns how many bytes have been skipped
      * This is strictly equivalent to
      *  return strlen(getData($length))
      * But could be far more efficient
      */
    function skip($length) { return strlen(getData($length)); }

    /**
      * Put back the reader in the state it was before the first call
      * to next()
      */
    function close() { return PEAR::raiseError("Abstract function call"); }

    /**
      * Sends the current file to the Writer $writer
      * The data will be sent by chunks of at most $bufferSize bytes
      */
    function sendData(&$writer, $bufferSize = 102400)
    {
        $filename = $this->getDataFilename();
        if($filename !== NULL)
            $writer->writeFile($filename);
        else {
            while(($data = $this->getData($bufferSize)) !== null)
                $writer->writeData($data);
        }
    }

    /**
      * Sends the whole reader to $writer and close the reader
      * If $autoClose is true (default), $writer will be closed after the extraction
      * Data will be sent to the reader by chunks of at most $bufferSize bytes
      */
    function extract(&$writer, $autoClose = true, $bufferSize = 102400)  //Default 100ko buffer
    {
        while(($error = $this->next()) === true)
        {
            $filename = $this->getFilename();
            $stat = $this->getStat();

            $writer->newFile(
                $this->getFilename(),
                $this->getStat(),
                $this->getMime()
            );
            $this->sendData($writer, $bufferSize);
        }
        $this->close();
        if($autoClose) {
            $writer->close();
        }
        if(PEAR::isError($error)) {
            return $error;
        }
    }

    /**
      * Extract only one file (given by the URL)
      * This is like calling select, sendData and (if $autoClose is true) closing the writer
      */
    function extractFile($filename, &$writer, $autoClose = true, $bufferSize = 102400)
    {
        if($this->select($filename)) {
            $this->sendData($writer, $bufferSize);
            $result = true;
        } else {
            $result = PEAR::raiseError("File $filename not found");
        }
        if($autoClose) {
            $writer->close();
        }
        return $result;
    }
}

?>