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

require_once "MemoryArchive.php";

/**
  * Write the files as a TAR archive
  */
class File_Archive_Writer_Tar extends File_Archive_Writer_MemoryArchive
{
    function tarHeader($filename, $stat)
    {
        $mode = $stat[2];
        $uid  = $stat[4];
        $gid  = $stat[5];
        $size = $stat[7];
        $time = $stat[9];
        $link = "";

        if($mode & 0x4000) {
            $type = 5;        // Directory
        } else if($mode & 0x8000) {
            $type = 0;        // Regular
        } else if($mode & 0xA000) {
            $type = 1;        // Link
            $link = @readlink($current);
        } else {
            $type = 9;        // Unknown
        }

        $pos = strrpos($filename, "/");
        if($pos !== FALSE) {
            $path = substr($filename, 0, $pos+1);
            $path = preg_replace("/^(\.{1,2}(\/|\\\))+/","",$path);

            $file = substr($filename, $pos+1);
        } else {
            $path = "";
            $file = $filename;
        }

        $blockbeg = pack("a100a8a8a8a12a12",
            $file,
            decoct($mode),
            sprintf("%6s ",decoct($uid)),
            sprintf("%6s ",decoct($gid)),
            sprintf("%11s ",decoct($size)),
            sprintf("%11s ",decoct($time))
            );

        $blockend = pack("a1a100a6a2a32a32a8a8a155a12",
            $type,
            $link,
            "ustar",
            "00",
            "Unknown",
            "Unknown",
            "",
            "",
            $path,
            "");

        $checksum = 8*ord(" ");
        for($i = 0; $i < 148; $i++)
            $checksum += ord($blockbeg{$i});
        for($i = 0; $i < 356; $i++)
            $checksum += ord($blockend{$i});

        $checksum = pack("a8",sprintf("%6s ",decoct($checksum)));

        return $blockbeg . $checksum . $blockend;
    }
    function tarFooter($stat)
    {
        $size = $stat[7];
        if($size % 512 > 0) {
            return pack("a".(512 - $size%512), "");
        } else {
            return "";
        }
    }

    function appendFile($filename, $dataFilename)
    {
        $stat = stat($dataFilename);

        $this->innerWriter->writeData($this->tarHeader($filename, $stat));
        $this->innerWriter->writeFile($dataFilename);
        $this->innerWriter->writeData($this->tarFooter($stat));
    }
    function appendFileData($filename, $stat, $data)
    {
        $size = strlen($data);
        $stat[7] = $size;

        $this->innerWriter->writeData($this->tarHeader($filename, $stat));
        $this->innerWriter->writeData($data);
        $this->innerWriter->writeData($this->tarFooter($stat));
    }
    function sendFooter()
    {
        $this->innerWriter->writeData(pack("a1024", ""));
    }
    function getMime() { return "application/x-tar"; }
}


/**
  * A tar archive cannot contain files with name of folders longer than 100 chars
  * This filter removes them
  */
require_once "File/Archive/Predicat.php";
class File_Archive_Predicat_TARCompatible extends File_Archive_Predicat
{
    function isTrue($source)
    {
        $pos = strrpos($source->getFilename(), "/");
        return ($pos<100) && (strlen($filename) - $pos < 155);
    }
}

?>