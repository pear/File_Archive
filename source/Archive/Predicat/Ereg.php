<?
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

require_once "File/Archive/Predicat.php";

/**
  * Keep only the files which name follow a given regular expression
  * See the PHP ereg function
  */
class File_Archive_Predicat_Ereg extends File_Archive_Predicat
{
    var $ereg;

    /**
      * $ereg is the regular expression
      * $source is the filtered source
      */
    function File_Archive_Predicat_Ereg($ereg)
    {
        $this->ereg = $ereg;
    }
    function isTrue($source)
    {
        return ereg($this->ereg, $source->getFilename());
    }
}

?>