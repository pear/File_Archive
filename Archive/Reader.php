<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Abstract base class for all the readers
 *
 * A reader is a compilation of serveral files that can be read
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
     * @return bool false iif no more files are available
     */
    function next()
    {
        return PEAR::raiseError("Reader abstract function call (next)");
    }

    /**
     * Move to the next file whose name is in directory $filename
     * or is exactly $filename
     *
     * @param string $filename Name of the file to find in the archive
     * @param bool $close If true, close the reader and search from the first file
     * @return bool whether the file was found in the archive or not
     */
    function select($filename, $close = true)
    {
        $std = $this->getStandardURL($filename);

        if ($close) {
            $error = $this->close();
            if (PEAR::isError($error)) {
                return $error;
            }
        }
        while (($error = $this->next()) === true) {
            $sourceName = $this->getFilename();
            if (
                  empty($std) ||

                //$std is a file
                  $std == $sourceName ||

                //$std is a directory
                strncmp($std.'/', $sourceName, strlen($std)+1) == 0
               ) {
                return true;
            }
        }
        return $error;
    }

    /**
     * Returns the standard path
     * Changes \ to /
     * Removes the .. and . from the URL
     * @param string $path a valid URL that may contain . or .. and \
     * @static
     */
    function getStandardURL($path)
    {
        if ($path == '.') {
            return '';
        }
        $std = str_replace("\\", "/", $path);
        while ($std != ($std = preg_replace("/[^\/:?]+\/\.\.\//", "", $std))) ;
        $std = str_replace("/./", "", $std);
        if (strncmp($std, "./", 2) == 0) {
            return substr($std, 2);
        } else {
            return $std;
        }
    }

    /**
     * Returns the name of the file currently read by the reader
     *
     * Warning: undefined behaviour if no call to next have been
     * done or if last call to next has returned false
     *
     * @return string Name of the current file
     */
    function getFilename()
    {
        return PEAR::raiseError("Reader abstract function call (getFilename)");
    }

    /**
     * Returns the list of filenames from the current pos to the end of the source
     * The source will be closed after having called this function
     * This function goes through the whole archive (which may be slow).
     * If you intend to work on the reader, doing it in one pass would be faster
     *
     * @return array filenames from the current pos to the end of the source
     */
    function getFileList()
    {
        $result = array();
        while ( ($error = $this->next()) === true) {
            $result[] = $this->getFilename();
        }
        $this->close();
        if (PEAR::isError($error)) {
            return $error;
        } else {
            return $result;
        }
    }

    /**
     * Returns an array of statistics about the file
     * (see the PHP stat function for more information)
     *
     * The returned array may be empty, even if readers should try
     * their best to return as many data as possible
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
     *
     * @return the name of the physical file containing the data
     *         or null if no such file exists
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
    function getData($length = -1)
    {
        return PEAR::raiseError("Reader abstract function call (getData)");
    }

    /**
     * Skip some data and returns how many bytes have been skipped
     * This is strictly equivalent to
     *  return strlen(getData($length))
     * But could be far more efficient
     */
    function skip($length)
    {
        $data = $this->getData($length);
        if (PEAR::isError($data)) {
            return $data;
        } else {
            return strlen($data);
        }
    }

    /**
     * Put back the reader in the state it was before the first call
     * to next()
     *
     * @param $recursive If true, close eventual inner writer even if
     *                   it was given opened?
     */
    function close($recursive = false)
    {
        return PEAR::raiseError("Reader abstract function call (close)");
    }

    /**
     * Sends the current file to the Writer $writer
     * The data will be sent by chunks of at most $bufferSize bytes
     */
    function sendData(&$writer, $bufferSize = 102400)
    {
        if (PEAR::isError($writer)) {
            return $writer;
        }

        $filename = $this->getDataFilename();
        if ($filename !== null) {
            $error = $writer->writeFile($filename);
            if (PEAR::isError($error)) {
                return $error;
            }
        } else {
            while (($data = $this->getData($bufferSize)) !== null) {
                if (PEAR::isError($data)) {
                    return $data;
                }
                $error = $writer->writeData($data);
                if (PEAR::isError($error)) {
                    return $error;
                }
            }
        }
    }

    /**
     * Sends the whole reader to $writer and close the reader
     *
     * @param File_Archive_Writer $writer Where to write the files of the reader
     * @param bool $autoClose If true, close $writer at the end of the function.
     *        Default value is true
     * @param int $bufferSize Size of the chunks that will be sent to the writer
     *        Default value is 100kB
     */
    function extract(&$writer, $autoClose = true, $bufferSize = 102400)
    {
        if (PEAR::isError($writer)) {
            $this->close();
            return $writer;
        }

        while (($error = $this->next()) === true) {
            $error = $writer->newFile(
                $this->getFilename(),
                $this->getStat(),
                $this->getMime()
            );
            if (PEAR::isError($error)) {
                break;
            }
            $error = $this->sendData($writer, $bufferSize);
            if (PEAR::isError($error)) {
                break;
            }
        }
        $this->close();
        if ($autoClose) {
            $writer->close();
        }
        if (PEAR::isError($error)) {
            return $error;
        }
    }

    /**
     * Extract only one file (given by the URL)
     *
     * @param string $filename URL of the file to extract from this
     * @param File_Archive_Writer $writer Where to write the file
     * @param bool $autoClose If true, close $writer at the end of the function
     *        Default value is true
     * @param int $bufferSize Size of the chunks that will be sent to the writer
     *        Default value is 100kB
     */
    function extractFile($filename, &$writer,
                         $autoClose = true, $bufferSize = 102400)
    {
        if (PEAR::isError($writer)) {
            return $writer;
        }

        if (($error = $this->select($filename)) === true) {
            $result = $this->sendData($writer, $bufferSize);
            if (!PEAR::isError($result)) {
                $result = true;
            }
        } else if ($error === false) {
            $result = PEAR::raiseError("File $filename not found");
        } else {
            $result = $error;
        }
        if ($autoClose) {
            $error = $writer->close();
            if (PEAR::isError($error)) {
                return $error;
            }
        }
        return $result;
    }

    /**
     * Returns a writer that will start writing at the current pos in the source
     * Any data (from current file or any other file) located after current pos
     * will be erased. No file will be erased though (so for file and directory
     * readers, the file and directory will not be deleted)
     * $this->close will be called. $this should not be used until the returned writer
     * has been closed
     *
     * Here is how to append a single file to an existing zip archive
     * <sample>
     *  $archive = File_Archive::read('archive.zip/');
     *  $file = File_Archive::read('foo.txt');
     *  $file->extract($source->makeAppendWriter());
     * </sample>
     *
     * Append the content of a tgz archive to an existing zip archive
     * <sample>
     *  $archive = File_Archive::read('archive.zip/');
     *  $file = File_Archive::read('archive.tgz/');
     *  $file->extract($source->makeAppendWriter());
     * </sample>
     *
     * @param int seek The new writer will be opened seek bytes after the current position
     *        Seek can be positive or negative
     *        If current file pos + seek < 0 or current file pos + seek > current file size,
     *        we have an undefined behaviour
     */
    function makeWriter($seek = 0)
    {
        return PEAR::raiseError("Reader abstract function call (makeWriter)");
    }

    /**
     * @return a writer that will allow to append files to an existing archive
     */
    function makeAppendWriter()
    {
        while ( ($error = $this->next()) === true ) { }
        if (PEAR::isError($error)) {
            $this->close();
            return $error;
        } else {
            return $this->makeWriter();
        }
    }
}

?>