<?php
namespace GenDiff\Tests;

use \PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class GenDiffTest extends TestCase
{
    public function testGenDiffJson()
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
  
        $this->fileSystem = vfsStream::setup('root', 444, $files);

        $pathToFile1 = $this->fileSystem->url() . '/before.json';
        $pathToFile2 = $this->fileSystem->url() . '/after.json';
        
        $diff = \GenDiff\Json\genDiff($pathToFile1, $pathToFile2);

        $result = <<<TEXT
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
TEXT;

        $this->assertEquals($diff, $result);
    }

    public function testGenDiffYaml()
    {
        $yaml1 = <<<YAML
host: hexlet.io
timeout: 50
proxy: 123.234.53.22
YAML;
        
        $yaml2 = <<<YAML
timeout: 20
verbose: true
host: hexlet.io
YAML;

        $files = [
            'before.yaml' => $yaml1,
            'after.yaml' => $yaml2
        ];

        $this->fileSystem = vfsStream::setup('root', 444, $files);

        $pathToFile1 = $this->fileSystem->url() . '/before.yaml';
        $pathToFile2 = $this->fileSystem->url() . '/after.yaml';

        $diff = \GenDiff\Yaml\genDiff($pathToFile1, $pathToFile2);

        $result = <<<TEXT
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
TEXT;
        $this->assertEquals($diff, $result);
    }
}
