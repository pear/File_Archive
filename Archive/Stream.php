<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Interface from a reader to a PHP stream
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

require_once "Reader.php";
require_once "Writer.php";

/**
 * @var array of File_Archive_Reader of File_Archive_Writer that will be used in
 *      File_Archive_Stream::stream_open()
 */
$File_Archive_Streams = array();

/**
 * Interface from a reader or writer to a PHP stream
 *
 * You can use it with a reader: fopen("wrapper://variable/path", "r");
 * where variable is the name of a global variable containing a File_Archive_Reader
 * Path is the path to a file or a folder. In case of a folder, the stream will point
 * to the first file encountered in the folder
 *
 * You can use it with a writer: fopen("wrapper://variable/path", "w");
 * where variable is the name of a global variable containing a File_Archive_Writer
 * path is the filename that must be appended to the path
 * The writer $variable will not be closed, even when the stream is closed (in order
 * to allow writing several files to this writer). You must close this writer when no
 * more files will be written.
 */
class File_Archive_Stream
{
    var $reader = null;
    var $writer = null;
    var $position = 0;
    var $stat = null;
    var $index = null;

    function stream_open($path, $mode, $options, &$opened_path)
    {
        global $File_Archive_Streams;

        $url = parse_url($path);
        $this->index = $url['host'];

        unset($this->reader);
        unset($this->writer);

        switch ($mode) {
        case 'r':
            $this->reader =& $File_Archive_Streams[$this->index];
            if (!empty($url['path'])) {
                $error = $this->reader->select(substr($url['path'], 1));
                if ($error !== true) {
                    return false;
                }
                $this->stat = $this->reader->getStat();
            } else {
                if ($this->reader->next() !== true) {
                    return false;
                }
            }
            return true;
        case 'w':
            $this->writer =& $File_Archive_Streams[$this->index];
            $error = $this->writer->newFile(substr($url['path'], 1));
            return !PEAR::isError($error);
        default:
            return false;
        }
    }

    function stream_write($data)
    {
        if (!isset($this->writer)) {
            return 0;
        }
        $this->writer->writeData($data);
        $this->position += strlen($data);
        return strlen($data);
    }

    function stream_read($count)
    {
        if (!isset($this->reader)) {
            return false;
        }
        $data = $this->reader->getData($count);
        return ($data == null ? false : $data);
    }

    function stream_eof()
    {
        if (!isset($this->reader)) {
            return true;
        }
        return $this->position >= $this->stat[7];
    }

    function stream_tell()
    {
        return $this->position;
    }

    function stream_seek($offset, $whence)
    {
        if (!isset($this->reader)) {
            return false;
        }
        switch ($whence) {
        case SEEK_SET:
            if ($offset >= $this->position) {
                $this->position +=
                    $this->reader->skip($offset - $this->position);
                return true;
            } else {
                return false;
            }
        case SEEK_CUR:
            if ($offset >= 0) {
                 $this->position += $this->reader->skip($offset);
                 return true;
            } else {
                 return false;
            }
        case SEEK_END:
            if ($this->stat[7] + $offset >= $position) {
                $this->position +=
                    $this->reader->skip($this->stat[7] + $offset - $position);
                 return true;
            } else {
                 return false;
            }
            break;

        default:
            return false;
        }
    }

    function stream_stat()
    {
        if (!isset($this->reader)) {
            return null;
        }
        return $this->stat;
    }
}

if(!stream_wrapper_register("filearchive", "File_Archive_Stream")) {
    die("Unable to register file_archive stream");
}

?>