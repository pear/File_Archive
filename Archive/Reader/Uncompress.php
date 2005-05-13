<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Recursively uncompress every file it finds
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

require_once "File/Archive/Reader.php";
require_once "ChangeName.php";

/**
 * Recursively uncompress every file it finds
 */
class File_Archive_Reader_Uncompress extends File_Archive_Reader_Relay
{
    /**
     * @var Array Stack of readers
     * @access private
     */
    var $readers = array();

    /**
     * @var File_Archive_Reader Reader from which all started (usefull to be
     *      able to close)
     * @access private
     */
    var $startReader;

    /**
     * @var Int Maximum depth of uncompression after the basicDir
     *          (that may contain some uncompression also)
     *          -1 means no limit
     * @access private
     */
    var $uncompressionLevel;

    /**
     * @var Int Depth of uncompression of the basicDir
     * @access private
     */
    var $baseDirCompressionLevel = null;

    /**
     * @var String Only files starting with $baseDir will be reported
     * @access private
     */
    var $baseDir = "";

    /**
     * @var Bool True if the current file has not been reported
     *           (used in setBaseDir to report an error if no file has been
     *           found)
     * @access private
     */
    var $currentFileDisplayed = true;

    function File_Archive_Reader_Uncompress(
                        &$innerReader, $uncompressionLevel = -1)
    {
        parent::File_Archive_Reader_Relay($innerReader);
        $this->startReader =& $innerReader;
        $this->uncompressionLevel = $uncompressionLevel;
    }

    /**
     * Attempt to change the current source (if the current file is an archive)
     * If this is the case, push the current source onto the stack and make the
     * good archive reader the current source. A file is considered as an
     * archive if its extension is one of tar, gz, zip, tgz
     *
     * @return bool whether the source has been pushed or not
     * @access private
     */
    function push()
    {
        if ($this->uncompressionLevel >= 0 &&
            $this->baseDirCompressionLevel !== null &&
            $this->uncompressionLevel + $this->baseDirCompressionLevel <= count($this->readers)
           ) {
           return false;
        }

        // Check the extension of the file (maybe we need to uncompress it?)
        $filename  = $this->source->getFilename();

        $extensions = explode('.', strtolower($filename));

        $reader =& $this->source;
        $nbUncompressions = 0;

        while (($extension = array_pop($extensions)) !== null) {
            $nbUncompressions++;
            unset($next);
            $next = File_Archive::readArchive($extension, $reader, $nbUncompressions == 1);
            if ($next === false) {
                $extensions = array();
            } else {
                unset($reader);
                $reader =& $next;
            }
        }
        if ($nbUncompressions == 1) {
            return false;
        } else {
            $this->readers[count($this->readers)] =& $this->source;
            unset($this->source);
            $this->source = new File_Archive_Reader_AddBaseName(
                $filename, $reader
            );
            return true;
        }
    }
    /**
     * @see File_Archive_Reader::close()
     */
    function next()
    {
        if (!$this->currentFileDisplayed) {
            $this->currentFileDisplayed = true;
            return true;
        }
        do {
            //Remove the readers we have completly read from the stack
            do {
                while (($error = $this->source->next()) === false) {
                    if (empty($this->readers)) {
                        return false;
                    }
                    $this->source->close();
                    $this->source =& $this->readers[count($this->readers)-1];
                    unset($this->readers[count($this->readers)-1]);
                }
                if (PEAR::isError($error)) {
                    return $error;
                }
                $currentFilename = $this->source->getFilename();

                if (strlen($currentFilename) < strlen($this->baseDir)) {
                    $goodFile =
                        (strncmp($this->baseDir, $currentFilename,
                                 strlen($currentFilename)) == 0 &&
                         $this->baseDir{strlen($currentFilename)} == '/');
                } else if (strlen($currentFilename) > strlen($this->baseDir)) {
                    $goodFile = empty($this->baseDir) ||
                       (strncmp($this->baseDir, $currentFilename,
                                strlen($this->baseDir)) == 0 &&
                        (substr($this->baseDir,-1) == '/' ||
                         $currentFilename{strlen($this->baseDir)} == '/')
                       );
                } else {
                    $goodFile = (strcmp($this->baseDir, $currentFilename) == 0);
                }
            } while (!$goodFile);

            if ($this->baseDirCompressionLevel === null &&
               strlen($currentFilename) >= strlen($this->baseDir)) {
                $this->baseDirCompressionLevel = count($this->readers);
            }
        } while ($this->push());
        return true;
    }
    /**
     * Efficiently filter out the files which URL does not start with $baseDir
     * Throws an error if the $baseDir can't be found
     * @return bool Whether baseDir was a directory or a file
     */
    function setBaseDir($baseDir)
    {
        $this->baseDirUncompressionLevel = null;
        $this->baseDir = $baseDir;

        $error = $this->next();
        if ($error === false) {
            return PEAR::raiseError("No directory $baseDir in inner reader");
        } else if (PEAR::isError($error)) {
            return $error;
        }
        $this->startReader = $this->source;
        $this->readers = array();

        $this->currentFileDisplayed = false;
        return strlen($this->getFilename())>strlen($baseDir);
    }
    /**
     * @see File_Archive_Reader::select()
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

        while (($error = $this->source->next()) === true) {
            $currentFilename = $this->source->getFilename().'/';
            $compLength = min(strlen($currentFilename), strlen($filename));
            if ( strncmp($currentFilename, $std, $compLength) == 0 ) {
                if (strlen($std) < strlen($currentFilename)) {
                    return true;
                } else if (! $this->push()) {
                    return false;
                }
            }
        }
        if (PEAR::isError($error)) {
            return $error;
        }
        return false;
    }

    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        for($i=0; $i<count($this->readers); ++$i) {
            $this->readers[$i]->close();
        }


        $this->readers = array();
        $error = parent::close();
        $this->source =& $this->startReader;
        return $error;
    }
}

?>