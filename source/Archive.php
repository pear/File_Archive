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

require_once "PEAR.php";

class File_Archive
{
    /**
      * Returns a reader to read the URL $directory
      * If the URL is a directory, it will recursively read that directory
      * If $uncompressionLevel is not null, the archives (files with extension tar, zip, gz or tgz) will
      *  be considered as directories (up to a depth of $uncompressionLevel if $uncompressionLevel > 0)
      * The reader will only read files with a directory depth of $directoryDepth
      * The reader will replace the given URL ($directory) with $symbolic in the public filenames
      *
      * Examples:
      * Considere the following file system
      * a.txt
      * b.tar (archive that contains the following files)
      *     c.txt
      *     d.tgz (archive that contains the following files)
      *         e.txt
      *         dir1/
      *             f.txt
      * dir2/
      *     g.txt
      *     dir3/
      *         h.tar (archive that contains the following files)
      *             i.txt
      *
      * read('.') will return a reader that gives access to following files (recursively read current dir):
      * a.txt
      * b.tar
      * dir2/g.txt
      * dir2/dir3/h.tar
      *
      * read('.', 'myBaseDir') will return the following reader:
      * myBaseDir/a.txt
      * myBaseDir/b.tar
      * myBaseDir/dir2/g.txt
      * myBaseDir/dir2/dir3/h.tar
      *
      * read('.', '', -1) will return the following reader (uncompress everything)
      * a.txt
      * b.tar/c.txt
      * b.tar/d.tgz/e.txt
      * b.tar/d.tgz/dir1/f.txt
      * dir2/g.txt
      * dir2/dir3/h.tar/i.txt
      *
      * read('.', '', 1) will uncompress only one level (so d.tgz will not be uncompressed):
      * a.txt
      * b.tar/c.txt
      * b.tar/d.tgz
      * dir2/g.txt
      * dir2/dir3/h.tar/i.txt
      *
      * read('.', '', 0, 0) will not recurse into subdirectories
      * a.txt
      * b.tar
      *
      * read('.', '', 0, 1) will recurse only one level in subdirectories
      * a.txt
      * b.tar
      * dir2/g.txt
      *
      * read('.', '', -1, 2) will uncompress everything and recurse in only 2 levels in subdirectories
      * or archives
      * a.txt
      * b.tar/c.txt
      * b.tar/d.tgz/e.txt
      * dir2/g.txt
      *
      * The recursion level is determined by the real path, not the symbolic one. So
      * read('.', 'myBaseDir', -1, 2) will result to the same files:
      * myBaseDir/a.txt
      * myBaseDir/b.tar/c.txt
      * myBaseDir/b.tar/d.tgz/e.txt (the public name is depth 3, but the real one is 2, so it is accepted)
      * myBaseDir/dir2/g.txt
      *
      * To read a single file, you can do read("a.txt", "public_name.txt")
      * Take care that if no public name is provided, the default one is empty
      */
    function read($directory, $symbolic='', $uncompression = 0, $directoryDepth = -1)
    {
        require_once "File/Archive/Reader/Uncompress.php";
        require_once "File/Archive/Reader/ChangeName.php";

        if($directoryDepth >= 0) {
            $uncompressionLevel = min($uncompression, $directoryDepth);
        } else {
            $uncompressionLevel = $uncompression;
        }

        //Find the first file in $directory
        $std = File_Archive_Reader::getStandardURL($directory);
        if(is_dir($directory)) {
            require_once "File/Archive/Reader/Directory.php";

            $result = new File_Archive_Reader_Uncompress(
                new File_Archive_Reader_Directory($std, '', $directoryDepth),
                $uncompressionLevel
            );
            if($directoryDepth >= 0) {
                require_once "File/Archive/Predicat/MaxDepth.php";

                $tmp = new File_Archive_Reader_Filter(
                    new File_Archive_Predicat_MaxDepth($directoryDepth),
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
            if(!empty($symbolic)) {
                $tmp = new File_Archive_Reader_AddBaseName(
                    $symbolic,
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
        } else if(is_file($directory)) {
            require_once "File/Archive/Reader/File.php";
            return new File_Archive_Reader_File($directory, $symbolic);
        } else {
            require_once "File/Archive/Reader/File.php";

            $pos = 0;
            do
            {
                if($pos == strlen($std)) {
                    return PEAR::raiseError("File $directory not found");
                }

                $pos = strpos($std, '/', $pos+1);
                if($pos === false) {
                    $pos = strlen($std);
                }

                $file = substr($std, 0, $pos);
            } while(!is_file($file));

            $result = new File_Archive_Reader_Uncompress(
                new File_Archive_Reader_File($file),
                $uncompressionLevel
            );
            $error = $result->setBaseDir($std);
            if(PEAR::isError($error)) {
                return PEAR::raiseError("File $directory not found");
            }

            if($directoryDepth >= 0) {
                require_once "File/Archive/Predicat/MaxDepth.php";

                $tmp = new File_Archive_Reader_Filter(
                    new File_Archive_Predicat($directoryDepth + substr_count(substr($std, $pos+1), '/')),
                    $result
                );
                unset($result);
                $result =& $tmp;
            }

            if($std != $symbolic) {
                $tmp = new File_Archive_Reader_ChangeBaseName(
                    $std,
                    $symbolic,
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
        }
        return $result;
    }
    function readMemory($memory, $filename, $stat=array(), $mime="application/octet-stream")
    {
        require_once "File/Archive/Reader/Memory.php";
        return new File_Archive_Reader_Memory($memory, $filename, $stat, $mime);
    }
    function readMulti()
    {
        require_once "File/Archive/Reader/Multi.php";
        return new File_Archive_Reader_Multi();
    }

    function filter($predicat, $source)
    {
        require_once "File/Archive/Reader/Filter.php";
        return new File_Archive_Reader_Filter($predicat, $source);
    }
    function predTrue()
    {
        require_once "File/Archive/Predicat/True.php";
        return new File_Archive_Predicat_True();
    }
    function predFalse()
    {
        require_once "File/Archive/Predicat/False.php";
        return new File_Archive_Predicat_False();
    }
    function predAnd()
    {
        require_once "File/Archive/Predicat/And.php";
        $pred = new File_Archive_Predicat_And();
        $args = func_get_args();
        foreach($args as $p)
            $pred->addPredicat($p);
        return $pred;
    }
    function predOr()
    {
        require_once "File/Archive/Predicat/Or.php";
        $pred = new File_Archive_Predicat_Or();
        $args = func_get_args();
        foreach($args as $p)
            $pred->addPredicat($p);
        return $pred;
    }
    function predNot($pred)
    {
        require_once "File/Archive/Predicat/Not.php";
        return new File_Archive_Predicat_Not($pred);
    }
    function predMinSize($size)
    {
        require_once "File/Archive/Predicat/MinSize.php";
        return new File_Archive_Predicat_MinSize($size);
    }
    function predMinTime($time)
    {
        require_once "File/Archive/Predicat/MinTime.php";
        return new File_Archive_Predicat_MinTime($time);
    }
    function predMaxDepth($depth)
    {
        require_once "File/Archive/Predicat/MaxDepth.php";
        return new File_Archive_Predicat_MaxDepth($depth);
    }
    function predExtension($list)
    {
        require_once "File/Archive/Predicat/Extension.php";
        return new File_Archive_Predicat_Extension($list);
    }
    function predEreg($ereg)
    {
        require_once "File/Archive/Predicat/Ereg.php";
        return new File_Archive_Predicat_Ereg($ereg);
    }
    function predEregi($ereg)
    {
        require_once "File/Archive/Predicat/Eregi.php";
        return new File_Archive_Predicat_Eregi($ereg);
    }

    function toMail($to, $headers, $message, &$mail = null)
    {
        require_once "File/Archive/Writer/Mail.php";
        return new File_Archive_Writer_Mail($to, $headers, $message, $mail);
    }
    function toFiles($baseDir = "")
    {
        require_once "File/Archive/Writer/Files.php";
        return new File_Archive_Writer_Files($baseDir);
    }
    function toMemory()
    {
        require_once "File/Archive/Writer/Memory.php";
        return new File_Archive_Writer_Memory();
    }
    function toMulti(&$a, &$b)
    {
        require_once "File/Archive/Writer/Multi.php";
        return new File_Archive_Writer_Multi($a, $b);
    }
    function toOutput($sendHeaders = true)
    {
        require_once "File/Archive/Writer/Output.php";
        return new File_Archive_Writer_Output($sendHeaders);
    }
    /**
      * @param $type can be one of Tar, Gzip, Tgz, Zip
      * The case of this parameter is not important
      */
    function toArchive($type, $filename, &$innerWriter, $stat = array(), $autoClose = true)
    {
        $realType = ucfirst($type);
        switch($realType)
        {
        case "Tgz":
            require_once "File/Archive/Writer/Tar.php";
            require_once "File/Archive/Writer/Gzip.php";
            return new File_Archive_Writer_Tar("$filename.tar",
                    new File_Archive_Writer_Gzip($filename, $innerWriter, $stat),
                    $stat, $autoClose);

        default:
            require_once "File/Archive/Writer/$realType.php";
            $class = "File_Archive_Writer_$realType";
            return new $class($filename, $innerWriter, $stat, $autoClose);
        }
    }
}

?>