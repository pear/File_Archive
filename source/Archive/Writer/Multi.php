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
  * Write to several writers
  */
//TODO: check that it's working with PHP4 (worried about the references)
class File_Archive_Writer_Multi extends File_Archive_Writer
{
    /**
      * @var File_Archive_Writer_Writer Data will be copied to these two writers
      */
    var $a, $b;

    function File_Archive_Writer_Multi(&$a, &$b)
    {
        $this->a =& $a;
        $this->b =& $b;
    }

    function newFile($filename, $stat, $mime="application/octet-stream")
    {
        $this->a->newFile($filename, $stat, $mime);
        $this->b->newFile($filename, $stat, $mime);
    }

    function writeData($data)
    {
        $this->a->writeData($data);
        $this->b->writeData($data);
    }

    function writeFile($filename)
    {
        $this->a->writeFile($filename);
        $this->b->writeFile($filename);
    }

    function close()
    {
        $this->a->close();
        $this->b->close();
    }
}
?>