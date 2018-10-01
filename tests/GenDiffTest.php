<?php
namespace GenDiff\Tests;
use \PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
class GenDiffTest extends TestCase
{
    public function setUp()
    {

      $files = [
          'before.json' => '{
            "host": "hexlet.io",
            "timeout": 50,
            "proxy": "123.234.53.22"
          }',
          'after.json' => '{
            "timeout": 20,
            "verbose": true,
            "host": "hexlet.io"
          }'
      ];

      $this->file_system = vfsStream::setup('root', 444, $files);
    }

    public function testGenDiff()
    {
        $pathToFile1 = $this->file_system->url() . '/before.json';
        $pathToFile2 = $this->file_system->url() . '/after.json';
        
        $diff = \GenDiff\genDiff($pathToFile1, $pathToFile2);

        $result = "{\n   host: hexlet.io\n + timeout: 20\n - timeout: 50\n - proxy: 123.234.53.22\n + verbose: true\n}";

        $this->assertEquals($diff, $result);
    }
}