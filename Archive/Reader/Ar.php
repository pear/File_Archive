<?php
/**
 * Read a file saved in Ar file format
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
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL
 * @version    CVS: $Id: 
 * @link       http://pear.php.net/package/File_Archive
 */

require_once "Archive.php";

/**
 * Read an Ar archive
 */
class File_Archive_Reader_Ar extends File_Archive_Reader_Archive
{

    /**
     * @var    array     Array that has a registry of the files thas has been asked
     *                   for their data. 
     *             
     *                   The files inside an ar file come with their data, each 'block'
     *                   of an ar file has the filename, size, uid, gid and the data (content).
     *                   So the data of the file should be asked only once (they not come in pieces)       
     *
     * @access private
     */
    var $_readedFiles = array ();
    
    /**
     * @var    boolean Flag that has tell us if we have read the header of the 
     *                 current file
     * @access private
     */
    var $_alreadyRead = false;

    /**
     * @var    string  Name of the file being read
     * @access private
     */
    var $_currentFilename = null;

    /**
     * @var    string  Stat properties of the file being read
     *                 It has: name, utime, uid, gid, mode, size and data
     * @access private
     */
    var $_currentStat = null; 

    /**
     * @see File_Archive_Reader::getFilename()
     */
    function getFilename() 
    {
        return $this->_currentFilename; 
    }
    
    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        $this->_currentFilename = null;
        $this->_currentStat = null;
        $this->_readedFiles = array ();
        return parent::close();
    }
    
    /**
     * @see File_Archive_Reader::getStat()
     */
    function getStat() 
    {
        return $this->_currentStat; 
    }
  
    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        $error = parent::next();
        if ($error !== true) {
            return $error;
        }       
     
        $filename = $this->source->getDataFilename();

        if (!$this->_alreadyRead) {
            $header = $this->source->getData(8);
            if ($header != "!<arch>\n") {
                return PEAR::raiseError("File {$filename} is not a valid Ar file format");
            }
            $this->_alreadyRead = true;
        }

       
        $name  = $this->source->getData(16);
        $mtime = $this->source->getData(12);
        $uid   = $this->source->getData(6);
        $gid   = $this->source->getData(6);
        $mode  = $this->source->getData(8);
        $size  = $this->source->getData(10);
        $delim = $this->source->getData(2);
        
        if ($delim == null) {
            return false;
        }
        // All files inside should have more than 0 bytes of size
        if ($size < 0) {
            return PEAR::raiseError("Files must be at least one byte long");
        }
        if (!$data = $this->source->getData($size)) {
            return PEAR::raiseError("There was a problem reading files inside {$filename}}");
        } 
        
        if ($size % 2 == 1) {
            $this->source->skip(1); //Dunno if this is the equivalent of fgetc($fp);
        }
        
        // if the filename starts with a length, then just read the bytes of it
        if (preg_match("/\#1\/(\d+)/", $name, $matches)) {
            $name = substr($data, 0, $matches[1]);
            $data = substr($data, $matches[1]);
            $size -= $matches[1];
        } else {
            // strip trailing spaces in name, so we can distinguish spaces in a filename with padding.
            $name = preg_replace ("/\s+$/", "", $name);
        }                
        $this->_leftLength = $size;
        if (!empty($name) && !empty($mtime) && !empty($uid) && 
            !empty($gid)  && !empty($mode) && !empty($size)) {
            $this->_currentFilename = $this->getStandardURL($name);
            $this->_currentStat = array('name'  => $name, 
                                        'mtime' => $mtime, 
                                        'uid'   => $uid,
                                        'gid'   => $gid,
                                        'mode'  => $mode,
                                        'size'  => $size,
                                        'data'  => $data);
        }
            
        return true;
    }  

    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {  
        /**
         * We need to verify if the file has already returned the data.
         *
         * The data can only be returned in one call, the data comes in a unique 
         * block and not in chunks or little pieces of data.
         */
        if (isset($this->_currentStat["name"])) {
            if (!in_array($this->_currentStat["name"], $this->_readedFiles)) {
                $this->_readedFiles[$this->_currentStat["name"]] = $this->_currentStat["name"];
                return $this->_currentStat["data"];            
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
?>