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
        $this->assertEquals($reader->getFilename(), "Memory");
        $this->assertEquals($reader->getData(1), "A");
        $this->assertEquals($reader->getData(2), "BC");
        $this->assertEquals($reader->getData(), "DEFGH");
        $this->assertFalse($reader->next());
        $reader->close();
    }
    function testFileReader()
    {
        $reader = File_Archive::read("test.php", "test.php");

        $this->assertTrue($reader->next());
        $this->assertEquals($reader->getData(), file_get_contents("test.php"));
        $this->assertFalse($reader->next());
        $reader->close();
    }
    function testMultiReader()
    {
        $reader = File_Archive::readMulti();

        $reader->addSource(File_Archive::read("test.php"));
        $reader->addSource(File_Archive::readMemory("A", "A.txt"));

        $this->assertTrue($reader->next());
        $this->assertEquals($reader->getFilename(), "test.php");
        $this->assertTrue($reader->next());
        $this->assertEquals($reader->getFilename(), "A.txt");
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

    //TODO: test the writers
}

$test = new PHPUnit_TestSuite("Test");
$result = PHPUnit::run($test);
echo $result->toString();
?>