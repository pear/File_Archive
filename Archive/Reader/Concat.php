<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * A reader that concatene the data of the files of a source
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

require_once "Relay.php";

/**
 * This reader provides one single file that is the concatenation of the data of
 * all the files of another reader
 */
class File_Archive_Reader_Concat extends File_Archive_Reader
{
    var $source;
    var $filename;
    var $stat;
    var $mime;
    var $opened = false;

    function File_Archive_Reader_Concat(&$source, $filename,
                                        $stat=array(), $mime=null)
    {
        $this->source =& $source;
        $this->filename = $filename;
        $this->stat = $stat;
        $this->mime = $mime;

        //Compute the total length
        $this->stat[7] = 0;
        while (($error = $source->next()) === true) {
            $sourceStat = $source->getStat();
            $this->stat[7] += $sourceStat[7];
        }
        if (PEAR::isError($error) || PEAR::isError($source->close())) {
            die("Error in File_Archive_Reader_Concat constructor ".
                "({$error->getMessage()}), cannot continue");
        }
    }

    /**
     * @see File_Archive_Reader::next()
     */
    function next()
    {
        if (!$this->opened) {
            return $this->opened = $this->source->next();
        } else {
            return false;
        }
    }
    /**
     * @see File_Archive_Reader::getFilename()
     */
    function getFilename() { return $this->filename; }
    /**
     * @see File_Archive_Reader::getStat()
     */
    function getStat() { return $this->stat; }
    /**
     * @see File_Archive_Reader::getMime()
     */
    function getMime()
    {
        return $this->mime==null ? parent::getMime() : $this->mime;
    }
    /**
     * @see File_Archive_Reader::getData()
     */
    function getData($length = -1)
    {
        if ($length == 0) {
            return '';
        }

        $result = '';
        while ($length == -1 || strlen($result)<$length) {
            $sourceData = $this->source->getData(
                $length==-1 ? -1 : $length - strlen($result)
            );

            if (PEAR::isError($sourceData)) {
                return $sourceData;
            }

            if ($sourceData == null) {
                $error = $this->source->next();
                if (PEAR::isError($error)) {
                    return $error;
                }
                if (!$error) {
                    break;
                }
            } else {
                $result .= $sourceData;
            }
        }
        return $result == '' ? null : $result;
    }
    /**
     * @see File_Archive_Reader::skip()
     */
    function skip($length)
    {
        $skipped = 0;
        while ($skipped < $length) {
            $sourceSkipped = $this->source->skip($length);
            if (PEAR::isError($sourceSkipped)) {
                return $skipped;
            }
            $skipped += $sourceSkipped;
        }
        return $skipped;
    }
    /**
     * @see File_Archive_Reader::close()
     */
    function close()
    {
        $error = $this->source->close();
        $this->opened = false;

        if (PEAR::isError($error)) {
            return $error;
        }
    }

}

?>