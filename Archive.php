<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Factory to access the most common File_Archive features
 * It uses lazy include, so you dont have to include the files from File/Archive/* directories
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

/**
 * To have access to PEAR::isError and PEAR::raiseError
 * We should probably use lazy include and remove this inclusion...
 */
require_once "PEAR.php";

/**
 * Factory to access the most common File_Archive features
 * It uses lazy include, so you dont have to include the files from File/Archive/* directories
 */
class File_Archive
{
    /**
     * Create a reader to read the URL $URL
     * If the URL is a directory, it will recursively read that directory
     * If $uncompressionLevel is not null, the archives (files with extension tar, zip, gz or tgz) will
     *  be considered as directories (up to a depth of $uncompressionLevel if $uncompressionLevel > 0)
     * The reader will only read files with a directory depth of $directoryDepth
     * The reader will replace the given URL ($URL) with $symbolic in the public filenames
     * The default symbolic name will be the last filename in the URL (or '' for directories)
     *
     * Examples:
     * Considere the following file system
     * <pre>
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
     * </pre>
     *
     * readUncompress('.') will return a reader that gives access to following files (recursively read current dir):
     * <pre>
     * a.txt
     * b.tar
     * dir2/g.txt
     * dir2/dir3/h.tar
     * </pre>
     *
     * readUncompress('.', 'myBaseDir') will return the following reader:
     * <pre>
     * myBaseDir/a.txt
     * myBaseDir/b.tar
     * myBaseDir/dir2/g.txt
     * myBaseDir/dir2/dir3/h.tar
     * </pre>
     *
     * readUncompress('.', '', -1) will return the following reader (uncompress everything)
     * <pre>
     * a.txt
     * b.tar/c.txt
     * b.tar/d.tgz/e.txt
     * b.tar/d.tgz/dir1/f.txt
     * dir2/g.txt
     * dir2/dir3/h.tar/i.txt
     * </pre>
     *
     * readUncompress('.', '', 1) will uncompress only one level (so d.tgz will not be uncompressed):
     * <pre>
     * a.txt
     * b.tar/c.txt
     * b.tar/d.tgz
     * dir2/g.txt
     * dir2/dir3/h.tar/i.txt
     * </pre>
     *
     * readUncompress('.', '', 0, 0) will not recurse into subdirectories
     * <pre>
     * a.txt
     * b.tar
     * </pre>
     *
     * readUncompress('.', '', 0, 1) will recurse only one level in subdirectories
     * <pre>
     * a.txt
     * b.tar
     * dir2/g.txt
     * </pre>
     *
     * readUncompress('.', '', -1, 2) will uncompress everything and recurse in only 2 levels in subdirectories
     * or archives
     * <pre>
     * a.txt
     * b.tar/c.txt
     * b.tar/d.tgz/e.txt
     * dir2/g.txt
     * </pre>
     *
     * The recursion level is determined by the real path, not the symbolic one. So
     * readUncompress('.', 'myBaseDir', -1, 2) will result to the same files:
     * <pre>
     * myBaseDir/a.txt
     * myBaseDir/b.tar/c.txt
     * myBaseDir/b.tar/d.tgz/e.txt (the public name is depth 3, but the real one is 2, so it is accepted)
     * myBaseDir/dir2/g.txt
     * </pre>
     *
     * To read a single file, you can do read('a.txt', 'public_name.txt')
     * If no public name is provided, the default one is the name of the file
     * read('dir2/g.txt') contains the single file named 'g.txt'
     * read('b.tar/c.txt') contains the single file named 'c.txt'
     *
     * Note: This function uncompress files reading their extension
     *       The compressed files must have a tar, zip, gz or tgz extension
     *       Since it is impossible for some URLs to use is_dir or is_file, this function may not work with
     *       URLs containing folders which name ends with such an extension
     */
    function read($URL, $symbolic=null, $uncompression = 0, $directoryDepth = -1)
    {
        require_once "File/Archive/Reader/Uncompress.php";
        require_once "File/Archive/Reader/ChangeName.php";

        if($directoryDepth >= 0) {
            $uncompressionLevel = min($uncompression, $directoryDepth);
        } else {
            $uncompressionLevel = $uncompression;
        }

        //Find the first file in $directory
        $std = File_Archive_Reader::getStandardURL($URL);
        if($symbolic == null) {
            $slashPos = strrpos($std, '/');
            if($slashPos === false) {
                $realSymbolic = '';
            } else {
                $realSymbolic = substr($std, $slashPos+1);
            }
        } else {
            $realSymbolic = $symbolic;
        }

        if(is_dir($URL)) {
            require_once "File/Archive/Reader/Directory.php";

            $result = new File_Archive_Reader_Uncompress(
                new File_Archive_Reader_Directory($std, '', $directoryDepth),
                $uncompressionLevel
            );
            if($directoryDepth >= 0) {
                require_once "File/Archive/Predicate/MaxDepth.php";

                $tmp = new File_Archive_Reader_Filter(
                    new File_Archive_Predicate_MaxDepth($directoryDepth),
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
            if(!empty($realSymbolic)) {
                $tmp = new File_Archive_Reader_AddBaseName(
                    $realSymbolic,
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
        } else if(is_file($URL) && substr($URL, -1)!='/') {
            require_once "File/Archive/Reader/File.php";
            return new File_Archive_Reader_File($URL, $realSymbolic);
        } else {
            require_once "File/Archive/Reader/File.php";

            $parsedURL = parse_url($std);
            $realPath = isset($parsedURL['path']) ? $parsedURL['path'] : '';

            $pos = 0;
            do
            {
                if($pos == strlen($realPath)) {
                    return new File_Archive_Reader_File($std, $realSymbolic);
                }

                $pos = strpos($realPath, '/', $pos+1);
                if($pos === false) {
                    $pos = strlen($realPath);
                }

                $file = substr($realPath, 0, $pos);
                $dotPos = strrpos($file, '.');
                $extension = '';
                if($dotPos !== false) {
                    $extension = substr($file, $dotPos+1);
                }
            } while(!File_Archive_Reader_Uncompress::isKnownExtension($extension) || is_dir($file));

            $parsedURL['path'] = $file;
            $file = '';

            //Rebuild the real URL with the smaller path
            if(isset($parsedURL['scheme'])) {
                $file .= $parsedURL['scheme'].'://';
            }
            if(isset($parsedURL['user'])) {
                $file .= $parsedURL['user'];
                if(isset($parsedURL['pass'])) {
                    $file .= ':'.$parsedURL['pass'];
                }
                $file .= '@';
            }
            if(isset($parsedURL['host'])) {
                $file .= $parsedURL['host'];
            }
            if(isset($parsedURL['port'])) {
                $file .= ':'.$parsedURL['port'];
            }
            $file .= $parsedURL['path'];
            if(isset($parsedURL['query'])) {
                $file .= '?'.$parsedURL['query'];
            }
            if(isset($parsedURL['fragment'])) {
                $file .= '#'.$parsedURL['fragment'];
            }

            // Build the reader
            $result = new File_Archive_Reader_Uncompress(
                new File_Archive_Reader_File($file),
                $uncompressionLevel
            );
            $isDir = $result->setBaseDir($std);
            if(PEAR::isError($isDir)) {
                return PEAR::raiseError("File $URL not found");
            }
            if($isDir && $symbolic==null) {
                $realSymbolic = '';
            }

            if($directoryDepth >= 0) {
                require_once "File/Archive/Predicate/MaxDepth.php";

                $tmp = new File_Archive_Reader_Filter(
                    new File_Archive_Predicate($directoryDepth + substr_count(substr($std, $pos+1), '/')),
                    $result
                );
                unset($result);
                $result =& $tmp;
            }

            if($std != $realSymbolic) {
                $tmp = new File_Archive_Reader_ChangeBaseName(
                    $std,
                    $realSymbolic,
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
        }
        return $result;
    }
    /**
     * @see File_Archive_Reader_Memory
     */
    function readMemory($memory, $filename, $stat=array(), $mime=null)
    {
        require_once "File/Archive/Reader/Memory.php";
        return new File_Archive_Reader_Memory($memory, $filename, $stat, $mime);
    }
    /**
     * @param array $sources Array of strings or readers that will be added to the multi reader
     *        If the parameter is a string, a reader will be built thanks to the read function
     * @see File_Archive_Reader_Multi File_Archive::read
     */
    function readMulti(&$sources = array())
    {
        require_once "File/Archive/Reader/Multi.php";
        $result = new File_Archive_Reader_Multi();
        foreach($sources as $index => $foo)
        {
            if(is_string($sources[index])) {
                $result->addSource(File_Archive::read($sources[index]));
            } else {
                $result->addSource($sources[$index]);
            }
        }
        return $result;
    }
    /**
     * @see File_Archive_Reader_Concat
     */
    function readConcat(&$source, $filename)
    {
        require_once "File/Archive/Reader/Concat.php";
        return new File_Archive_Reader_Concat($source, $filename);
    }

    /**
     * @see File_Archive_Reader_Filter
     */
    function filter($predicate, $source)
    {
        require_once "File/Archive/Reader/Filter.php";
        return new File_Archive_Reader_Filter($predicate, $source);
    }
    /**
     * @see File_Archive_Predicate_True
     */
    function predTrue()
    {
        require_once "File/Archive/Predicate/True.php";
        return new File_Archive_Predicate_True();
    }
    /**
     * @see File_Archive_Predicate_False
     */
    function predFalse()
    {
        require_once "File/Archive/Predicate/False.php";
        return new File_Archive_Predicate_False();
    }
    /**
     * @see File_Archive_Predicate_And
     */
    function predAnd()
    {
        require_once "File/Archive/Predicate/And.php";
        $pred = new File_Archive_Predicate_And();
        $args = func_get_args();
        foreach($args as $p)
            $pred->addPredicate($p);
        return $pred;
    }
    /**
     * @see File_Archive_Predicate_Or
     */
    function predOr()
    {
        require_once "File/Archive/Predicate/Or.php";
        $pred = new File_Archive_Predicate_Or();
        $args = func_get_args();
        foreach($args as $p)
            $pred->addPredicate($p);
        return $pred;
    }
    /**
     * @see File_Archive_Predicate_Not
     */
    function predNot($pred)
    {
        require_once "File/Archive/Predicate/Not.php";
        return new File_Archive_Predicate_Not($pred);
    }
    /**
     * @see File_Archive_Predicate_MinSize
     */
    function predMinSize($size)
    {
        require_once "File/Archive/Predicate/MinSize.php";
        return new File_Archive_Predicate_MinSize($size);
    }
    /**
     * @see File_Archive_Predicate_MinTime
     */
    function predMinTime($time)
    {
        require_once "File/Archive/Predicate/MinTime.php";
        return new File_Archive_Predicate_MinTime($time);
    }
    /**
     * @see File_Archive_Predicate_MaxDepth
     */
    function predMaxDepth($depth)
    {
        require_once "File/Archive/Predicate/MaxDepth.php";
        return new File_Archive_Predicate_MaxDepth($depth);
    }
    /**
     * @see File_Archive_Predicate_Extension
     */
    function predExtension($list)
    {
        require_once "File/Archive/Predicate/Extension.php";
        return new File_Archive_Predicate_Extension($list);
    }
    /**
     * @see File_Archive_Predicate_MIME
     */
    function predMIME($list)
    {
        require_once "File/Archive/Predicate/MIME.php";
        return new File_Archive_Predicate_MIME($list);
    }
    /**
     * @see File_Archive_Predicate_Ereg
     */
    function predEreg($ereg)
    {
        require_once "File/Archive/Predicate/Ereg.php";
        return new File_Archive_Predicate_Ereg($ereg);
    }
    /**
     * @see File_Archive_Predicate_Eregi
     */
    function predEregi($ereg)
    {
        require_once "File/Archive/Predicate/Eregi.php";
        return new File_Archive_Predicate_Eregi($ereg);
    }
    /**
     * @see File_Archive_Predicate_Custom
     */
    function predCustom($expression)
    {
        require_once "File/Archive/Predicate/Custom.php";
        return new File_Archive_Predicate_Custom($expression);
    }

    /**
     * @see File_Archive_Writer_Mail
     */
    function toMail($to, $headers, $message, &$mail = null)
    {
        require_once "File/Archive/Writer/Mail.php";
        return new File_Archive_Writer_Mail($to, $headers, $message, $mail);
    }
    /**
     * @see File_Archive_Writer_Files
     */
    function toFiles($baseDir = "")
    {
        require_once "File/Archive/Writer/Files.php";
        return new File_Archive_Writer_Files($baseDir);
    }
    /**
     * @see File_Archive_Writer_Memory
     */
    function toMemory(&$data = null)
    {
        require_once "File/Archive/Writer/Memory.php";
        return new File_Archive_Writer_Memory($data);
    }
    /**
     * @see File_Archive_Writer_Multi
     */
    function toMulti(&$a, &$b)
    {
        require_once "File/Archive/Writer/Multi.php";
        return new File_Archive_Writer_Multi($a, $b);
    }
    /**
     * @see File_Archive_Writer_Output
     */
    function toOutput($sendHeaders = true)
    {
        require_once "File/Archive/Writer/Output.php";
        return new File_Archive_Writer_Output($sendHeaders);
    }
    /**
     * @param string $filename name of the archive file
     * @param File_Archive_Writer $innerWriter writer where the archive will be written
     * @param string $type can be one of Tar, Gz, Tgz, Zip (default is the extension of $filename)
     *        The case of this parameter is not important
     * @param array $stat Statistics of the archive (see stat function)
     * @param bool $autoClose If set to true, $innerWriter will be closed when the returned archive is close
     *        Default value is true.
     */
    function toArchive($filename, &$innerWriter, $type=null, $stat = array(), $autoClose = true)
    {
        if($type == null) {
            $dotPos = strrpos($filename, '.');
            if($dotPos !== false) {
                $type = substr($filename, $dotPos+1);
            } else {
                return PEAR::raiseError("Unknown archive type for $filename (you should specify a third argument)");
            }
        }
        $type = ucfirst($type);
        if($type == "Gz") {
            $type = "Gzip";
        }
        switch($type)
        {
        case "Tgz":
            require_once "File/Archive/Writer/Tar.php";
            require_once "File/Archive/Writer/Gzip.php";
            return new File_Archive_Writer_Tar("$filename.tar",
                    new File_Archive_Writer_Gzip($filename, $innerWriter, $stat),
                    $stat, $autoClose);
        case "Tar":
        case "Zip":
        case "Gzip":
            require_once "File/Archive/Writer/$type.php";
            $class = "File_Archive_Writer_$type";
            return new $class($filename, $innerWriter, $stat, $autoClose);
        default:
            return PEAR::raiseError("Extension $type unknown");
        }
    }
}

?>