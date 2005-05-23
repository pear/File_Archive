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
     * @var    int       The number of files to read to reach the end of the
     *                   current ar file
     *
     * @access private
     */
    var $_nbBytesLeft = 0;

    /**
     * @var    boolean   Flag set if their is a 1 byte footer after the data
     *                   of the current ar file
     *
     * @access private
     */
    var $_footer = false;

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
        $this->_nbBytesLeft = 0;
        $this->_footer = false;
        $this->_alreadyRead = false;
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

        $this->source->skip(
            $this->_nbBytesLeft + ($this->_footer ? 1 : 0)
        );

        $filename = $this->source->getDataFilename();

        if (!$this->_alreadyRead) {
            $header = $this->source->getData(8);
            if ($header != "!<arch>\n") {
                return PEAR::raiseError("File {$filename} is not a valid Ar file format (starts with $header)");
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

        $this->_footer = ($size % 2 == 1);

        // if the filename starts with a length, then just read the bytes of it
        if (preg_match("/\#1\/(\d+)/", $name, $matches)) {
            echo "matches    : {$matches[1]}\n";
            $name = $this->source->getData($matches[1]);
            $size -= $matches[1];
        } else {
            // strip trailing spaces in name, so we can distinguish spaces in a filename with padding.
            $name = preg_replace ("/\s+$/", "", $name);
        }

        $this->_nbBytesLeft = $size;
        if (empty($name) || empty($mtime) || empty($uid) ||
            empty($gid)  || empty($mode) || empty($size)) {
            return PEAR::raiseError("An ar field is empty");
        }

        $this->_currentFilename = $this->getStandardURL($name);
        $this->_currentStat = array('name'  => $name,
                                    'mtime' => $mtime,
                                    'uid'   => $uid,
                                    'gid'   => $gid,
                                    'mode'  => $mode,
                                    'size'  => $size);

        return true;
    }

    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if ($length == -1) {
            $length = $this->_nbBytesLeft;
        } else {
            $length = min($length, $this->_nbBytesLeft);
        }
        if ($length == 0) {
            return null;
        } else {
            $this->_nbBytesLeft -= $length;
            return $this->source->getData($length);
        }
    }
}
?>