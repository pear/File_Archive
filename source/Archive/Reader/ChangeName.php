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

require_once "Relay.php";

class File_Archive_Reader_AddBaseName extends File_Archive_Reader_Relay
{
    var $baseName;
    function File_Archive_Reader_AddBaseName($baseName='', &$source)
    {
        parent::File_Archive_Reader_Relay($source);
        $this->baseName = $this->getStandardURL($baseName);
    }

    function getFilename()
    {
        $name = parent::getFilename();
        return $this->baseName.
               (empty($this->baseName) || empty($name) ? '': '/').
               $name;
    }
}

class File_Archive_Reader_ChangeBaseName extends File_Archive_Reader_Relay
{
    var $oldBaseName;
    var $newBaseName;

    function File_Archive_Reader_ChangeBaseName($oldBaseName, $newBaseName, &$source)
    {
        parent::File_Archive_Reader_Relay($source);
        $this->oldBaseName = $this->getStandardURL($oldBaseName);
        if(substr($this->oldBaseName, -1)=='/')
            $this->oldBaseName = substr($this->oldBaseName, 0, strlen($this->oldBaseName)-1);

        $this->newBaseName = $this->getStandardURL($newBaseName);
        if(substr($this->newBaseName, -1)=='/')
            $this->newBaseName = substr($this->newBaseName, 0, strlen($this->newBaseName)-1);
    }

    function getFilename()
    {
        $name = parent::getFilename();
        return $this->newBaseName.
               (empty($this->newBaseName) || strlen($name)<=strlen($this->oldBaseName)+1 ?'':'/').
               substr($name, strlen($this->oldBaseName)+1);
    }
}

?>