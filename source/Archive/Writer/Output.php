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


require_once "File/Archive/Writer.php";

/**
  * Writer to the standard output
  * It will concatenate the files that it receive
  * It may send some headers, but will do so only for the first file
  */
class File_Archive_Writer_Output extends File_Archive_Writer
{
    /**
      * @var Bool If true, the Content-type and Content-disposition headers will be sent
      * The file will be considered as an attachment and the MIME will be deduced from its extension
      */
    var $sendHeaders;

    /**
      * @param $sendHeaders see the variable
      */
    function File_Archive_Writer_Output($sendHeaders = true)
    {
        $this->sendHeaders = $sendHeaders;
    }
    function newFile($filename, $stat, $mime="application/octet-stream")
    {
        if($this->sendHeaders) {
            header("Content-type: $mime");
            header("Content-disposition: attachment; filename=$filename");
            $this->sendHeaders = FALSE;
        }
    }
    function writeData($data) { echo $data; }
    function writeFile($filename) { readfile($filename); }
}

?>