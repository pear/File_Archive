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
  * Evaluates to true iif one at least of the predicats given as constructor parameters evaluate to true
  */
class File_Archive_Predicat_Or extends File_Archive_Predicat
{
    var $preds;
    function File_Archive_Reader_Predicat_And()
    {
        $this->preds = func_get_args();
    }
    function addPredicat($pred)
    {
        $this->preds[] = $pred;
    }
    function isTrue($source)
    {
        foreach($this->preds as $p)
        {
            if($p->isTrue($source)) {
                return true;
            }
        }
        return false;
    }
}

?>