<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Factory to access the most common File_Archive features
 * It uses lazy include, so you dont have to include the files from
 * File/Archive/* directories
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
 * It uses lazy include, so you dont have to include the files from
 * File/Archive/* directories
 */
class File_Archive
{
    /**
     * Create a reader to read the URL $URL.
     * If the URL is a directory, it will recursively read that directory.
     * If $uncompressionLevel is not null, the archives (files with extension
     * tar, zip, gz or tgz) will be considered as directories (up to a depth of
     * $uncompressionLevel if $uncompressionLevel > 0). The reader will only
     * read files with a directory depth of $directoryDepth. It reader will
     * replace the given URL ($URL) with $symbolic in the public filenames
     * The default symbolic name is the last filename in the URL (or '' for
     * directories)
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
     * readUncompress('.') will return a reader that gives access to following
     * files (recursively read current dir):
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
     * readUncompress('.', '', -1) will return the following reader (uncompress
     * everything)
     * <pre>
     * a.txt
     * b.tar/c.txt
     * b.tar/d.tgz/e.txt
     * b.tar/d.tgz/dir1/f.txt
     * dir2/g.txt
     * dir2/dir3/h.tar/i.txt
     * </pre>
     *
     * readUncompress('.', '', 1) will uncompress only one level (so d.tgz will
     * not be uncompressed):
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
     * readUncompress('.', '', 0, 1) will recurse only one level in
     * subdirectories
     * <pre>
     * a.txt
     * b.tar
     * dir2/g.txt
     * </pre>
     *
     * readUncompress('.', '', -1, 2) will uncompress everything and recurse in
     * only 2 levels in subdirectories or archives
     * <pre>
     * a.txt
     * b.tar/c.txt
     * b.tar/d.tgz/e.txt
     * dir2/g.txt
     * </pre>
     *
     * The recursion level is determined by the real path, not the symbolic one.
     * So readUncompress('.', 'myBaseDir', -1, 2) will result to the same files:
     * <pre>
     * myBaseDir/a.txt
     * myBaseDir/b.tar/c.txt
     * myBaseDir/b.tar/d.tgz/e.txt (accepted because the real depth is 2)
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
     *       Since it is impossible for some URLs to use is_dir or is_file, this
     *       function may not work with
     *       URLs containing folders which name ends with such an extension
     */
    function read($URL, $symbolic = null,
                  $uncompression = 0, $directoryDepth = -1,
                  &$source=null)
    {
        require_once "File/Archive/Reader/Uncompress.php";
        require_once "File/Archive/Reader/ChangeName.php";

        //No need to un compress more than $directoryDepth
        //That's not perfect, and some archives will still be uncompressed just //to be filtered out :(
        if ($directoryDepth >= 0) {
            $uncompressionLevel = min($uncompression, $directoryDepth);
        } else {
            $uncompressionLevel = $uncompression;
        }

        $std = File_Archive_Reader::getStandardURL($URL);

        //Modify the symbolic name if necessary
        if ($symbolic == null) {
            $slashPos = strrpos($std, '/');
            if ($slashPos === false) {
                $realSymbolic = '';
            } else {
                $realSymbolic = substr($std, $slashPos+1);
            }
        } else {
            $realSymbolic = $symbolic;
        }

        if (empty($URL) || is_dir($URL) && $source == null) {
            require_once "File/Archive/Reader/Directory.php";

            $result = new File_Archive_Reader_Uncompress(
                new File_Archive_Reader_Directory($std, '', $directoryDepth),
                $uncompressionLevel
            );
            if ($directoryDepth >= 0) {
                require_once "File/Archive/Predicate/MaxDepth.php";

                $tmp = new File_Archive_Reader_Filter(
                    new File_Archive_Predicate_MaxDepth($directoryDepth),
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
            if (!empty($realSymbolic)) {
                $tmp = new File_Archive_Reader_AddBaseName(
                    $realSymbolic,
                    $result
                );
                unset($result);
                $result =& $tmp;
            }
        } else if (is_file($URL) && substr($URL, -1)!='/' && $source == null) {
            require_once "File/Archive/Reader/File.php";
            return new File_Archive_Reader_File($URL, $realSymbolic);
        } else {
            require_once "File/Archive/Reader/File.php";

            $parsedURL = parse_url($std);
            $realPath = isset($parsedURL['path']) ? $parsedURL['path'] : '';

            $pos = 0;
            do {
                if ($pos == strlen($realPath)) {
                    if($source == null) {
                        return new File_Archive_Reader_File(
                                            $std, $realSymbolic);
                    } else {
                        $file = ($realPath == '.' ? '' : $realPath);
                        break;
                    }
                }

                $pos = strpos($realPath, '/', $pos+1);
                if ($pos === false) {
                    $pos = strlen($realPath);
                }

                $file = substr($realPath, 0, $pos);
                $dotPos = strrpos($file, '.');
                $extension = '';
                if ($dotPos !== false) {
                    $extension = substr($file, $dotPos+1);
                }
            } while (
                !File_Archive_Reader_Uncompress::isKnownExtension($extension) ||
                (is_dir($file) && $source==null));

            $parsedURL['path'] = $file;
            $file = '';

            //Rebuild the real URL with the smaller path
            if (isset($parsedURL['scheme'])) {
                $file .= $parsedURL['scheme'].'://';
            }
            if (isset($parsedURL['user'])) {
                $file .= $parsedURL['user'];
                if (isset($parsedURL['pass'])) {
                    $file .= ':'.$parsedURL['pass'];
                }
                $file .= '@';
            }
            if (isset($parsedURL['host'])) {
                $file .= $parsedURL['host'];
            }
            if (isset($parsedURL['port'])) {
                $file .= ':'.$parsedURL['port'];
            }
            $file .= $parsedURL['path'];
            if (isset($parsedURL['query'])) {
                $file .= '?'.$parsedURL['query'];
            }
            if (isset($parsedURL['fragment'])) {
                $file .= '#'.$parsedURL['fragment'];
            }

            // Build the reader
            if($source == null) {
                $result = new File_Archive_Reader_Uncompress(
                            new File_Archive_Reader_File($file),
                            $uncompressionLevel
                          );
            } else {
                require_once "File/Archive/Reader/Filter.php";
                require_once "File/Archive/Predicate/Select.php";
                $result = new File_Archive_Reader_Uncompress(
                            new File_Archive_Reader_Filter(
                              new File_Archive_Predicate_Select($file),
                              $source
                            ),
                            $uncompressionLevel
                          );
            }
            $isDir = $result->setBaseDir($std);
            if (PEAR::isError($isDir)) {
                return PEAR::raiseError("File $URL not found");
            }
            if ($isDir && $symbolic==null) {
                $realSymbolic = '';
            }

            if ($directoryDepth >= 0) {
                require_once "File/Archive/Predicate/MaxDepth.php";

                $tmp = new File_Archive_Reader_Filter(
                    new File_Archive_Predicate(
                        $directoryDepth +
                        substr_count(substr($std, $pos+1), '/')
                    ),
                    $result
                );
                unset($result);
                $result =& $tmp;
            }

            if ($std != $realSymbolic) {
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
     * Contains only one file with data read from a memory buffer
     *
     * @param string $memory content of the file
     * @param string $filename public name of the file
     * @param array $stat statistics of the file. Index 7 (size) will be
     *        overwritten to match the size of $memory
     * @param string $mime mime type of the file. Default will determine the
     *        mime type thanks to the extension of $filename
     * @see File_Archive_Reader_Memory
     */
    function readMemory($memory, $filename, $stat=array(), $mime=null)
    {
        require_once "File/Archive/Reader/Memory.php";
        return new File_Archive_Reader_Memory($memory, $filename, $stat, $mime);
    }
    /**
     * Contains several other sources. Take care the sources don't have several
     * files with the same filename. The sources are given as a parameter, or
     * can be added thanks to the reader addSource method
     *
     * @param array $sources Array of strings or readers that will be added to
     *        the multi reader. If the parameter is a string, a reader will be
     *        built thanks to the read function
     * @see   File_Archive_Reader_Multi, File_Archive::read()
     */
    function readMulti(&$sources = array())
    {
        require_once "File/Archive/Reader/Multi.php";
        $result = new File_Archive_Reader_Multi();
        foreach ($sources as $index => $foo) {
            if (is_string($sources[index])) {
                $URLreader = File_Archive::read($sources[index]);
                if (PEAR::isError($URLreader)) {
                    return $URLreader;
                }
                $result->addSource(URLreader);
            } else {
                $result->addSource($sources[$index]);
            }
        }
        return $result;
    }
    /**
     * Make the files of a source appear as one large file whose content is the
     * concatenation of the content of all the files
     *
     * @param File_Archive_Reader $source The source whose files must be
     *        concatened
     * @param string $filename name of the only file of the created reader
     * @param array $stat statistics of the file. Index 7 (size) will be
     *        overwritten to match the total size of the files
     * @param string $mime mime type of the file. Default will determine the
     *        mime type thanks to the extension of $filename
     * @see   File_Archive_Reader_Concat
     */
    function readConcat(&$source, $filename, $stat=array(), $mime=null)
    {
        require_once "File/Archive/Reader/Concat.php";
        return new File_Archive_Reader_Concat($source, $filename, $stat, $mime);
    }

    /**
     * Removes from a source the files that do not follow a given predicat
     *
     * @param File_Archive_Predicate $predicate Only the files for which
     *        $predicate->isTrue() will be kept
     * @param File_Archive_Reader $source Source that will be filtered
     * @see   File_Archive_Reader_Filter
     */
    function filter($predicate, $source)
    {
        require_once "File/Archive/Reader/Filter.php";
        return new File_Archive_Reader_Filter($predicate, $source);
    }
    /**
     * Predicate that always evaluate to true
     *
     * @see File_Archive_Predicate_True
     */
    function predTrue()
    {
        require_once "File/Archive/Predicate/True.php";
        return new File_Archive_Predicate_True();
    }
    /**
     * Predicate that always evaluate to false
     *
     * @see File_Archive_Predicate_False
     */
    function predFalse()
    {
        require_once "File/Archive/Predicate/False.php";
        return new File_Archive_Predicate_False();
    }
    /**
     * Predicate that evaluates to the logical AND of the parameters
     * You can add other predicates thanks to the
     * File_Archive_Predicate_And::addPredicate() function
     *
     * @param File_Archive_Predicate (any number of them)
     * @see File_Archive_Predicate_And
     */
    function predAnd()
    {
        require_once "File/Archive/Predicate/And.php";
        $pred = new File_Archive_Predicate_And();
        $args = func_get_args();
        foreach ($args as $p) {
            $pred->addPredicate($p);
        }
        return $pred;
    }
    /**
     * Predicate that evaluates to the logical OR of the parameters
     * You can add other predicates thanks to the
     * File_Archive_Predicate_Or::addPredicate() function
     *
     * @param File_Archive_Predicate (any number of them)
     * @see File_Archive_Predicate_Or
     */
    function predOr()
    {
        require_once "File/Archive/Predicate/Or.php";
        $pred = new File_Archive_Predicate_Or();
        $args = func_get_args();
        foreach ($args as $p) {
            $pred->addPredicate($p);
        }
        return $pred;
    }
    /**
     * Negate a predicate
     *
     * @param File_Archive_Predicate $pred Predicate to negate
     * @see File_Archive_Predicate_Not
     */
    function predNot($pred)
    {
        require_once "File/Archive/Predicate/Not.php";
        return new File_Archive_Predicate_Not($pred);
    }
    /**
     * Evaluates to true iif the file is larger than a given size
     *
     * @param int $size the minimal size of the files (in Bytes)
     * @see File_Archive_Predicate_MinSize
     */
    function predMinSize($size)
    {
        require_once "File/Archive/Predicate/MinSize.php";
        return new File_Archive_Predicate_MinSize($size);
    }
    /**
     * Evaluates to true iif the file has been modified after a given time
     *
     * @param int $time Unix timestamp of the minimal modification time of the
     *        files
     * @see File_Archive_Predicate_MinTime
     */
    function predMinTime($time)
    {
        require_once "File/Archive/Predicate/MinTime.php";
        return new File_Archive_Predicate_MinTime($time);
    }
    /**
     * Evaluates to true iif the file has less that a given number of
     * directories in its path
     *
     * @param int $depth Maximal number of directories in path of the files
     * @see File_Archive_Predicate_MaxDepth
     */
    function predMaxDepth($depth)
    {
        require_once "File/Archive/Predicate/MaxDepth.php";
        return new File_Archive_Predicate_MaxDepth($depth);
    }
    /**
     * Evaluates to true iif the extension of the file is in a given list
     *
     * @param array or string $list List or comma separated string of possible
     * extension of the files
     * @see File_Archive_Predicate_Extension
     */
    function predExtension($list)
    {
        require_once "File/Archive/Predicate/Extension.php";
        return new File_Archive_Predicate_Extension($list);
    }
    /**
     * Evaluates to true iif the MIME type of the file is in a given list
     *
     * @param array or string $list List or comma separated string of possible
     *        MIME types of the files. You may enter wildcards like "image/*" to
     *        select all the MIME in class image
     * @see   File_Archive_Predicate_MIME, MIME_Type::isWildcard()
     */
    function predMIME($list)
    {
        require_once "File/Archive/Predicate/MIME.php";
        return new File_Archive_Predicate_MIME($list);
    }
    /**
     * Evaluates to true iif the name of the file follow a given regular
     * expression
     *
     * @param string $ereg regular expression that the filename must follow
     * @see File_Archive_Predicate_Ereg, ereg()
     */
    function predEreg($ereg)
    {
        require_once "File/Archive/Predicate/Ereg.php";
        return new File_Archive_Predicate_Ereg($ereg);
    }
    /**
     * Evaluates to true iif the name of the file follow a given regular
     * expression (case insensitive version)
     *
     * @param string $ereg regular expression that the filename must follow
     * @see File_Archive_Predicate_Eregi, eregi
     */
    function predEregi($ereg)
    {
        require_once "File/Archive/Predicate/Eregi.php";
        return new File_Archive_Predicate_Eregi($ereg);
    }
    /**
     * Custom predicate built by supplying a string expression
     *
     * Example:
     *   new File_Archive_Predicate_Custom("return strlen($name)<100;")
     *   new File_Archive_Predicate_Custom("strlen($name)<100;")
     *   new File_Archive_Predicate_Custom("strlen($name)<100")
     *   new File_Archive_Predicate_Custom("strlen($source->getFilename())<100")
     *
     * @param string $expression String containing an expression that evaluates
     *        to a boolean. If the expression doesn't contain a return
     *        statement, it will be added at the begining of the expression
     *        A ';' will be added at the end of the expression so that you don't
     *        have to write it. You may use the $name variable to refer to the
     *        current filename (with path...), $time for the modification time
     *        (unix timestamp), $size for the size of the file in bytes, $mime
     *        for the MIME type of the file
     * @see   File_Archive_Predicate_Custom
     */
    function predCustom($expression)
    {
        require_once "File/Archive/Predicate/Custom.php";
        return new File_Archive_Predicate_Custom($expression);
    }

    /**
     * Send the files as a mail attachment
     *
     * @param Mail $mail Object used to send mail (see Mail::factory)
     * @param array or String $to An array or a string with comma separated
     *        recipients
     * @param array $headers The headers that will be passed to the Mail_mime
     *        object
     * @param string $message Text body of the mail
     * @see File_Archive_Writer_Mail
     */
    function toMail($to, $headers, $message, &$mail = null)
    {
        require_once "File/Archive/Writer/Mail.php";
        return new File_Archive_Writer_Mail($to, $headers, $message, $mail);
    }
    /**
     * Write the files on the hard drive
     *
     * @param string $baseDir if specified, the files will be created in that
     *        directory. If they don't exist, the directories will automatically
     *        be created
     * @see   File_Archive_Writer_Files
     */
    function toFiles($baseDir = "")
    {
        require_once "File/Archive/Writer/Files.php";
        return new File_Archive_Writer_Files($baseDir);
    }
    /**
     * Send the content of the files to a memory buffer
     *
     * @param out $data if specified, the data will be written to this buffer
     *        Else, you can retrieve the buffer with the
     *        File_Archive_Writer_Memory::getData() function
     * @see   File_Archive_Writer_Memory
     */
    function toMemory(&$data = null)
    {
        require_once "File/Archive/Writer/Memory.php";
        return new File_Archive_Writer_Memory($data);
    }
    /**
     * Duplicate the writing operation on two writers
     *
     * @param File_Archive_Writer $a, $b writers where data will be duplicated
     * @see File_Archive_Writer_Multi
     */
    function toMulti(&$a, &$b)
    {
        require_once "File/Archive/Writer/Multi.php";
        return new File_Archive_Writer_Multi($a, $b);
    }
    /**
     * Send the content of the files to the standard output (so to the client
     * for a website)
     *
     * @param bool $sendHeaders If true some headers will be sent to force the
     *        download of the file. Default value is true
     * @see   File_Archive_Writer_Output
     */
    function toOutput($sendHeaders = true)
    {
        require_once "File/Archive/Writer/Output.php";
        return new File_Archive_Writer_Output($sendHeaders);
    }
    /**
     * Compress the data to a tar, gz, tar/gz or zip format
     *
     * @param string $filename name of the archive file
     * @param File_Archive_Writer $innerWriter writer where the archive will be
     *        written
     * @param string $type can be one of Tar, Gz, Tgz, Zip (default is the
     *        extension of $filename). The case of this parameter is not
     *        important
     * @param array $stat Statistics of the archive (see stat function)
     * @param bool $autoClose If set to true, $innerWriter will be closed when
     *        the returned archive is close. Default value is true.
     */
    function toArchive($filename, &$innerWriter, $type = null,
                       $stat = array(), $autoClose = true)
    {
        if ($type == null) {
            $dotPos = strrpos($filename, '.');
            if ($dotPos !== false) {
                $type = substr($filename, $dotPos+1);
            } else {
                return PEAR::raiseError(
                    "Unknown archive type for $filename ".
                    "(you should specify a third argument)");
            }
        }
        $type = ucfirst($type);
        if ($type == "Gz") {
            $type = "Gzip";
        }
        switch($type) {
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