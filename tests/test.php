<?php

require_once 'File/Archive.php';
require_once 'PHPUnit.php';

/*
 * Actually more to check that the syntax is OK
 * than the actual functionnality
 */
class Test extends PHPUnit_TestCase
{
    function testMemoryReader()
    {
        $reader = File_Archive::readMemory("ABCDEFGH", "Memory");

        $this->assertTrue($reader->next());
        $this->assertEquals("Memory", $reader->getFilename());
        $this->assertEquals("A", $reader->getData(1));
        $this->assertEquals("BC", $reader->getData(2));
        $this->assertEquals("DEFGH", $reader->getData());
        $this->assertFalse($reader->next());
        $reader->close();
    }
    function testFileReader()
    {
        $reader = File_Archive::read("test.php", "test.php");

        $this->assertTrue($reader->next());
        $this->assertEquals(file_get_contents("test.php"), $reader->getData());
        $this->assertFalse($reader->next());
        $reader->close();
    }
    function testURLReader()
    {
        $reader = File_Archive::read("http://www.google.com", "google.html");

        $this->assertTrue($reader->next());

        $data = $reader->getData();
        $this->assertFalse(empty($data));
        $reader->close();
    }
    function testAdvancedURLReader()
    {
        $reader = File_Archive::read("http://poocl.la-grotte.org/downloads/PEAR2/poocl.tar/");
        $nbFiles = '';
        while($reader->next())
        {
            $nbFiles++;
        }
        $this->assertEquals(39, $nbFiles);
        $reader->close();
    }
    function testDownloadAdvancedURL()
    {
        $reader = File_Archive::read("http://poocl.la-grotte.org/downloads/PEAR2/poocl.tar/File/Archive.php");
        $this->assertTrue($reader->next());
        $data = $reader->getData();
        $reader->close();
    }
    function testMultiReader()
    {
        $reader = File_Archive::readMulti();

        $reader->addSource(File_Archive::read("test.php", "test.php"));
        $reader->addSource(File_Archive::readMemory("A", "A.txt"));

        $this->assertTrue($reader->next());
        $this->assertEquals("test.php", $reader->getFilename());
        $this->assertTrue($reader->next());
        $this->assertEquals("A.txt", $reader->getFilename());
        $this->assertFalse($reader->next());

        $reader->close();
    }
    function testConcatReader()
    {
        $source = File_Archive::readMulti();
        $source->addSource(File_Archive::readMemory("ABCDE", "fo"));
        $source->addSource(File_Archive::readMemory("FGHIJ", "ob"));
        $source->addSource(File_Archive::readMemory("KLMNO", "ar"));
        $reader = File_Archive::readConcat($source, "foobar");

        $this->assertTrue($reader->next());
        $this->assertEquals("ABC", $reader->getData(3));
        $this->assertEquals("DEF", $reader->getData(3));
        $this->assertEquals("GHIJKLMNO", $reader->getData());
        $this->assertFalse($reader->next());

        $reader->close();
    }

    function testFilter()
    {
        $reader = File_Archive::filter(File_Archive::predFalse(), File_Archive::read('.'));
        $this->assertFalse($reader->next());
        $reader->close();
    }

    function testTrue()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicat = File_Archive::predTrue();
        $this->assertTrue($predicat->isTrue($source));
    }
    function testFalse()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicat = File_Archive::predFalse();
        $this->assertFalse($predicat->isTrue($source));
    }
    function testAnd()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicat = File_Archive::predAnd(
            File_Archive::predTrue(),
            File_Archive::predFalse(),
            File_Archive::predTrue());
        $this->assertFalse($predicat->isTrue($source));

        $predicat = File_Archive::predAnd(
            File_Archive::predTrue(),
            File_Archive::predTrue());
        $this->assertTrue($predicat->isTrue($source));
    }
    function testOr()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicat = File_Archive::predOr(
            File_Archive::predFalse(),
            File_Archive::predFalse(),
            File_Archive::predTrue());
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predAnd(
            File_Archive::predFalse(),
            File_Archive::predFalse());
        $this->assertFalse($predicat->isTrue($source));
    }
    function testNot()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicat = File_Archive::predNot(File_Archive::predTrue());
        $this->assertFalse($predicat->isTrue($source));
    }
    function testMinSize()
    {
        $source = File_Archive::readMemory('123456789', 'A.txt'); $source->next();

        $predicat = File_Archive::predMinSize(9);
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predMinSize(10);
        $this->assertFalse($predicat->isTrue($source));
    }
    function testMinTime()
    {
        $source = File_Archive::read('test.php');  $source->next();
        $predicat = File_Archive::predMinTime(filemtime('test.php')-1);
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predMinTime(filemtime('test.php')+1);
        $this->assertFalse($predicat->isTrue($source));
    }
    function testMaxDepth()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicat = File_Archive::predMaxDepth(4);
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predMaxDepth(3);
        $this->assertFalse($predicat->isTrue($source));
    }
    function testExtension()
    {
        $source = File_Archive::read('test.php');  $source->next();
        $predicat = File_Archive::predExtension(array('php', 'txt'));
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predExtension('txt');
        $this->assertFalse($predicat->isTrue($source));
    }
    function testEreg()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicat = File_Archive::predEreg('/A');
        $this->assertTrue($predicat->isTrue($source));
    }
    function testEregi()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicat = File_Archive::predEregi('/a');
        $this->assertTrue($predicat->isTrue($source));
    }
    function testCustom()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicat = File_Archive::predCustom('return ereg("/A",$source->getFilename());');
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predCustom('ereg("/A",$source->getFilename());');
        $this->assertTrue($predicat->isTrue($source));

        $predicat = File_Archive::predCustom('ereg("/A",$source->getFilename())');
        $this->assertTrue($predicat->isTrue($source));
    }

    //TODO: test the toMail, toFiles
    function testToMemory()
    {
        $source = File_Archive::read('test.php');
        $dest = File_Archive::toMemory();

        $source->extract($dest);
        $this->assertEquals(file_get_contents('test.php'), $dest->getData());
    }
    function _testArchive($archiveFormat, $extension)
    {
        $filename = "test.$extension";

        $source = File_Archive::read('test.php');
        $compressed = File_Archive::toMemory();
        $source->extract(
            File_Archive::toArchive(
                $archiveFormat,
                'test.php',
                $compressed
            )
        );

        require_once "File/Archive/Reader/Uncompress.php";

        $source = new File_Archive_Reader_Uncompress(
            File_Archive::readMemory($compressed->getData(), $filename)
        );

        $uncompressed = File_Archive::toMemory();
        $source->extract($uncompressed);

        $this->assertEquals(file_get_contents('test.php'), $uncompressed->getData());
    }
    function testTar() { $this->_testArchive('tar', 'tar'); }
    function testZip() { $this->_testArchive('zip', 'zip'); }
    function testGzip() { $this->_testArchive('gzip', 'gz'); }
    function testTgz() { $this->_testArchive('tgz', 'tgz'); }
    function testWriteZip()
    {
        $source = File_Archive::readMulti();
        $source->addSource(File_Archive::read('test.php'));
        $source->addSource(File_Archive::readMemory("This is a dynamic file put in the ZIP archive", "dynamic.txt"));
        $source->extract(
            File_Archive::toArchive('zip', 'test.zip',
                File_Archive::toFiles()
            )
        );
        $source->close();
    }

    function testMultiWriter()
    {
        $source = File_Archive::readMemory("ABCDEF", "A.txt");
        $source->extract(
            File_Archive::toMulti(
                $a = File_Archive::toMemory(),
                $b = File_Archive::toMemory()
            )
        );
        $this->assertEquals($a->getData(), "ABCDEF");
        $this->assertEquals($b->getData(), "ABCDEF");
    }
}

$test = new PHPUnit_TestSuite("Test");
$result = PHPUnit::run($test);
echo $result->toHTML();
?>