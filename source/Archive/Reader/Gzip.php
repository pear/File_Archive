<?php
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

class File_Archive_Reader_Gzip extends File_Archive_Reader_Archive
{
    var $data = NULL;
    var $offset = 0;
    var $alreadyRead = FALSE;

    var $name = NULL;
    var $comment = NULL;
    var $hasName = FALSE;
    var $hasComment = FALSE;

    function close()
    {
        $this->data = NULL;
        $this->offset = 0;
        $this->alreadyRead = FALSE;
        parent::close();
    }

    function next()
    {
        if($this->alreadyRead)
            return FALSE;
        $this->alreadyRead = true;

        $header = $this->source->getData(10);

        $id = unpack("H2id1/H2id2/C1tmp/C1flags",substr($header,0,4));
        if($id['id1'] != "1f" || $id['id2'] != "8b")
            die("Not valid gz file (wrong header)");

        $temp = decbin($id['flags']);
        $this->hasName = ($temp & 0x8);
        $this->hasComment = ($temp & 0x4);

        $this->name = "";
        if($this->hasName)
        {
            while(($char = $this->source->getData(1)) !== "\0")
            {
                if($char === NULL)
                    die("Not valid gz file (unexpected end of archive reading filename)");
                $this->name .= $char;
            }
            $this->name = $this->getStandardURL($this->name);
        }
        $this->comment = "";
        if($this->hasComment)
        {
            while(($char = $this->source->getData(1)) !== "\0")
            {
                if($char === NULL)
                    die("Not valid gz file (unexpected end of archive reading comment)");
                $this->comment .= $char;
            }
        }

        $this->data = $this->source->getData();

        $temp = unpack("Vcrc32/Visize",substr($this->data,-8));
        $crc32 = $temp['crc32'];
        $size = $temp['isize'];

        $this->data = gzinflate(substr($this->data,0,strlen($this->data)-8));
        $this->offset = 0;

        if($size  != strlen($this->data))
            die("Not valid gz file (size error {$size} != ".strlen($this->data).")");
        if($crc32 != crc32 ($this->data))
            die("Not valid gz file (checksum error)");

        return TRUE;
    }
    function getFilename()
    {
        return $this->name;
    }
    function getStat()
    {
        return array(
            7 => strlen($this->data)
        );
    }
    function getData($length = -1)
    {
        if($length == -1)
            $actualLength = strlen($this->data) - $this->offset;
        else
            $actualLength = min(strlen($this->data) - $this->offset, $length);

        if($actualLength == 0)
            return NULL;
        else {
            $result = substr($this->data, $this->offset, $actualLength);
            $this->offset += $actualLength;
            return $result;
        }
    }
    function skip($length)
    {
        $this->offset += $length;
        return $length;
    }
}

?>