<?php
/**
 * Write data to a file and save as an ar
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
 * Write the files as an AR archive
 */
class File_Archive_Writer_Ar extends File_Archive_Writer_Archive
{
    
    /**
     * @var    string   Current data of the file. 
     * @access private
     */
    var $_buffer = "";

    /**
     * @var    string   Filename of the current filename
     * @access private
     */
    var $_currentFilename = null;

    /**
     * @var    boolean  Flag: use buffer or not.
     * @access private
     */
    var $_useBuffer;
    
    /**
     * @var    array    Stats of the current filename
     * @access private 
     */
    var $_currentStat = array ();

    /**
     * Flush the memory we have in the ar. 
     *
     * Build the buffer if its called at the end or initialize
     * it if we are just creating it from the start.
     */
    function flush()
    {
         if ($this->_currentFilename != null) {
             $this->_currentStat[7] = strlen($this->_buffer);
             $this->_currentStat['size'] = $this->_currentStat[7];
             $currentSize = $this->_currentStat[7];
             if ($this->_useBuffer) {
                 //if file length is > than 16..
                 if (strlen($this->_currentFilename) > 16) {
                     $currentSize += strlen($this->_currentFilename);
                     $this->innerWriter->writeData(sprintf("#1/%-13d", strlen($this->_currentFilename)));
                     $this->innerWriter->writeData(sprintf("%-12d%-6d%-6d%-8s%-10d",
                                                           $this->_currentStat[9],
                                                           $this->_currentStat[4],
                                                           $this->_currentStat[5],
                                                           $this->_currentStat[2],
                                                           $currentSize));
                     $this->innerWriter->writeData("`\n".$this->_currentFilename);
                 } else {
                     $this->innerWriter->writeData(sprintf("%-16s", $this->_currentFilename));
                     $this->innerWriter->writeData(sprintf("%-12d%-6d%-6d%-8s%-10d`\n",
                                                           $this->_currentStat[9],
                                                           $this->_currentStat[4],
                                                           $this->_currentStat[5],
                                                           $this->_currentStat[2],
                                                           $this->_currentStat[7])); 
                 }
                 $this->innerWriter->writeData($this->_buffer);
                 
                 if ($currentSize % 2 == 1) {
                     $this->innerWriter->writeData("\n");
                 }
             } else {
                 if ($currentSize % 2 == 1) {
                     $this->innerWriter->writeData("\n");
                 }
             }            
         }
         $this->_buffer = "";
    }

    /**
     * @see File_Archive_Writer::newFile()
     *
     */
    function newFile($filename, $stat = array (), 
                     $mime = "application/octet-stream") 
    {
        $this->flush();
        
        /**
         * If the file is empty, there's no reason to have a buffer
         * or use memory 
         */
        $this->_useBuffer = !isset($stats[7]); 
        $this->_currentFilename = $filename;
        $this->_currentStat = $stat;       
    }

    /**
     * @see File_Archive_Writer::close()
     */
    function close()
    {
        $this->innerWriter->writeData("!<arch>\n");
        $this->flush();
        parent::close();
    }

    /**
     * @see File_Archive_Writer::writeData()
     */
    function writeData($data)
    {
        if ($this->_useBuffer) {
            $this->_buffer .= $data;
        } else {
            $this->innerWriter->writeData($data);
        }

    }
    /**
     * @see File_Archive_Writer::writeFile()
     */
    function writeFile($filename)
    {
        if ($this->_useBuffer) {
            $this->_buffer .= file_get_contents($filename);
        } else {
            $this->innerWriter->writeFile($filename);
        }
    }
}