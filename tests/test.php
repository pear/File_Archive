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
    function _testURLReader()
    {
        $reader = File_Archive::read("http://www.google.com", "google.html");

        $this->assertTrue($reader->next());

        $data = $reader->getData();
        $this->assertFalse(empty($data));
        $reader->close();
    }
    function _testAdvancedURLReader()
    {
        $reader = File_Archive::read("http://poocl.la-grotte.org/downloads/PEAR2/poocl.tar/");
        $nbFiles = '';
        while ($reader->next())
        {
            $nbFiles++;
        }
        $this->assertEquals(39, $nbFiles);
        $reader->close();
    }
    function _testDownloadAdvancedURL()
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
        $predicate = File_Archive::predTrue();
        $this->assertTrue($predicate->isTrue($source));
    }
    function testFalse()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicate = File_Archive::predFalse();
        $this->assertFalse($predicate->isTrue($source));
    }
    function testAnd()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicate = File_Archive::predAnd(
            File_Archive::predTrue(),
            File_Archive::predFalse(),
            File_Archive::predTrue());
        $this->assertFalse($predicate->isTrue($source));

        $predicate = File_Archive::predAnd(
            File_Archive::predTrue(),
            File_Archive::predTrue());
        $this->assertTrue($predicate->isTrue($source));
    }
    function testOr()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicate = File_Archive::predOr(
            File_Archive::predFalse(),
            File_Archive::predFalse(),
            File_Archive::predTrue());
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predAnd(
            File_Archive::predFalse(),
            File_Archive::predFalse());
        $this->assertFalse($predicate->isTrue($source));
    }
    function testNot()
    {
        $source = File_Archive::readMemory('', 'A.txt'); $source->next();
        $predicate = File_Archive::predNot(File_Archive::predTrue());
        $this->assertFalse($predicate->isTrue($source));
    }
    function testMinSize()
    {
        $source = File_Archive::readMemory('123456789', 'A.txt'); $source->next();

        $predicate = File_Archive::predMinSize(9);
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predMinSize(10);
        $this->assertFalse($predicate->isTrue($source));
    }
    function testMinTime()
    {
        $source = File_Archive::read('test.php');  $source->next();
        $predicate = File_Archive::predMinTime(filemtime('test.php')-1);
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predMinTime(filemtime('test.php')+1);
        $this->assertFalse($predicate->isTrue($source));
    }
    function testMaxDepth()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicate = File_Archive::predMaxDepth(4);
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predMaxDepth(3);
        $this->assertFalse($predicate->isTrue($source));
    }
    function testExtension()
    {
        $source = File_Archive::read('test.php');  $source->next();
        $predicate = File_Archive::predExtension(array('php', 'txt'));
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predExtension('txt');
        $this->assertFalse($predicate->isTrue($source));
    }
    function testEreg()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicate = File_Archive::predEreg('/A');
        $this->assertTrue($predicate->isTrue($source));
    }
    function testEregi()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicate = File_Archive::predEregi('/a');
        $this->assertTrue($predicate->isTrue($source));
    }
    function testCustom()
    {
        $source = File_Archive::readMemory('', 'A/B/C/1/A.txt'); $source->next();

        $predicate = File_Archive::predCustom('return ereg("/A",$source->getFilename());');
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predCustom('ereg("/A",$source->getFilename());');
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predCustom('ereg("/A",$source->getFilename())');
        $this->assertTrue($predicate->isTrue($source));
    }
    function testMIME()
    {
        $source = File_Archive::readMemory('', 'A.jpg'); $source->next();

        $predicate = File_Archive::predMIME("image/jpeg");
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predMIME("image/*");
        $this->assertTrue($predicate->isTrue($source));

        $predicate = File_Archive::predMIME("application/*");
        $this->assertFalse($predicate->isTrue($source));
    }

    //TODO: test the toMail
    function testToMemory()
    {
        $source = File_Archive::read('test.php');
        $dest = File_Archive::toMemory();

        $source->extract($dest);
        $this->assertEquals(file_get_contents('test.php'), $dest->getData());
    }
    function _testArchive($extension)
    {
        $filename = "test.$extension";

        $source = File_Archive::read('test.php');
        $generated = $extension=='gz' || $extension=='bz2' ? 'test' : 'test.php';

        $source->extract(
            File_Archive::toArchive(
                $filename,
                $compressed = File_Archive::toMemory()
            )
        );

        require_once "File/Archive/Reader/Uncompress.php";

        $compressedReader = $compressed->makeReader();
        $source = File_Archive::readSource($compressedReader, "$filename/$generated");

        if(PEAR::isError($source))
            echo ($extension);
        $source->extract(File_Archive::toVariable($uncompressed));
        $this->assertEquals(file_get_contents('test.php'), $uncompressed);
    }
    function testTar() { $this->_testArchive('tar'); }
    function testZip() { $this->_testArchive('zip'); }
    function testGzip() { $this->_testArchive('gz'); }
    function testTgz() { $this->_testArchive('tgz'); }
    function testTbz() { $this->_testArchive('tbz'); }
    function testBZ2() { $this->_testArchive('bz2'); }
    function testWriteBZip2()
    {
        //Build the writer
        $writer = File_Archive::toArchive('example1.tbz', File_Archive::toFiles());

        //Write the list of even number in [0..999]
        $writer->newFile("even.txt");
        for ($i=0; $i<1000; $i+=2)
        {
            $writer->writeData("$i\n");
        }

        //Write the list of odd number in [0..999]
        $writer->newFile("odd.txt");
        for ($i=1; $i<1000; $i+=2)
        {
            $writer->writeData("$i\n");
        }

        //Close the writer
        $writer->close();
    }
    function testDirectories()
    {
        $source = File_Archive::read('../Archive');
        $source->extract(File_Archive::toArchive('up.zip', File_Archive::toFiles()));

        $source = File_Archive::read('up.zip/');
        $appendedData = File_Archive::read('test.php');
        $appendedData->extract($source->makeAppendWriter());
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
    function _testReadArchive()
    {
        $source = File_Archive::readArchive('tar', File_Archive::read('up.tar'));
        while($source->next())
            echo $source->getFilename()."\n";
    }
}

$test = new PHPUnit_TestSuite("Test");
$result = PHPUnit::run($test);
echo $result->toHTML();
?>