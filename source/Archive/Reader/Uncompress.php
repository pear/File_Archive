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

require_once "File/Archive/Reader.php";
require_once "ChangeName.php";

/**
  * Recursively uncompress every file it finds
  */
class File_Archive_Reader_Uncompress extends File_Archive_Reader_Relay
{
    /**
      * @var Array Stack of readers
      */
    var $readers = array();

    /**
      * @var File_Archive_Reader Reader from which all started (usefull to be able to close)
      */
    var $startReader;

    /**
      * @var Int Maximum depth of uncompression after the basicDir (that may contain some uncompression also)
      * -1 means no limit
      */
    var $uncompressionLevel;

    /**
      * @var Int Depth of uncompression of the basicDir
      */
    var $baseDirCompressionLevel = null;

    /**
      * @var String Only files starting with $baseDir will be reported
      */
    var $baseDir = "";

    /**
      * @var Bool True if the current file has not been reported
      * (used in setBaseDir to report an error if no file has been found)
      */
    var $currentFileDisplayed = true;

    function File_Archive_Reader_Uncompress(&$innerReader, $uncompressionLevel = -1)
    {
        $this->source =& $innerReader;
        $this->startReader =& $innerReader;
        $this->uncompressionLevel = $uncompressionLevel;
    }
    function push()
    {
        if($this->uncompressionLevel>=0 && ($this->baseDirCompressionLevel !== null) &&
           $this->uncompressionLevel + $this->baseDirCompressionLevel <= count($this->readers))
           return false;

        // Check the extension of the file (maybe we need to uncompress it?)
        $filename  = $this->source->getFilename();

        $pos = strrpos($filename, '.');
        $extension = "";
        if($pos !== FALSE) {
            $extension = strtolower(substr($filename, $pos+1));
        }

        switch($extension)
        {
        case "tar":
            require_once "Tar.php";
            $this->readers[count($this->readers)] =& $this->source;
            $next = new File_Archive_Reader_AddBaseName($filename,
                new File_Archive_Reader_Tar($this->source, true)
            );
            break;
        case "gz":
            require_once "Gzip.php";
            $this->readers[count($this->readers)] =& $this->source;
            $next = new File_Archive_Reader_AddBaseName($filename,
                new File_Archive_Reader_Gzip($this->source, true)
            );
            break;
        case "zip":
            require_once "Zip.php";
            $this->readers[count($this->readers)] =& $this->source;
            $next = new File_Archive_Reader_AddBaseName($filename,
                new File_Archive_Reader_Zip($this->source, true)
            );
            break;
        case "tgz":
            require_once "Tar.php";
            require_once "Gzip.php";
            $this->readers[count($this->readers)] =& $this->source;
            $next = new File_Archive_Reader_AddBaseName($filename,
                new File_Archive_Reader_Tar(
                    new File_Archive_Reader_Gzip(
                        $this->source, true
                    )
                )
            );
            break;
        default: return false;
        }
        unset($this->source);
        $this->source = $next;
        return true;
    }
    function next()
    {
        if(!$this->currentFileDisplayed) {
            $this->currentFileDisplayed = true;
            return true;
        }
        while(true)
        {
            //Remove the readers we have completly read from the stack
            do
            {
                while(! $this->source->next())
                {
                    if(empty($this->readers)) {
                        return false;
                    }
                    $this->source =& $this->readers[count($this->readers)-1];
                    unset($this->readers[count($this->readers)-1]);
                }
                $currentFilename = $this->source->getFilename();
                $compLen = min(strlen($currentFilename), strlen($this->baseDir));

            } while(strncmp($this->baseDir, $currentFilename, $compLen)!=0);

            if($this->baseDirCompressionLevel === null &&
               strlen($currentFilename)>=strlen($this->baseDir)) {
                $this->baseDirCompressionLevel = count($this->readers);
            }

            if(! $this->push())
                return true;
        }
    }
    function setBaseDir($baseDir)
    {
        $this->baseDirUncompressionLevel = null;
        $this->baseDir = $baseDir;

        if(! $this->next()) {
            return PEAR::raiseError("No directory $baseDir in inner reader");
        }
        $this->currentFileDisplayed = false;
    }
    function select($filename)
    {
        $std = $this->getStandardURL($filename);

        $this->close();

        while($this->source->next())
        {
            $currentFilename = $this->source->getFilename().'/';
            $compLength = min(strlen($currentFilename), strlen($filename));
            if( strncmp($currentFilename, $std, $compLength) == 0 ) {
                if(strlen($std) < strlen($currentFilename)) {
                    return true;
                } else if(! $this->push()) {
                    return false;
                }
            }
        }
        return false;
    }

    function close()
    {
        $this->readers = array();
        parent::close();
        $this->source =& $this->startReader;
    }
}

?>