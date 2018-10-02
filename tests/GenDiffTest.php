<?php
namespace GenDiff\Tests;

use \PHPUnit\Framework\TestCase;

class GenDiffTest extends TestCase
{
    public function testGenDiffJson()
    {
        $before = __DIR__ . "/files/before.json";
        $after = __DIR__ . "/files/after.json";
        $correctDiff = __DIR__ . "/files/correctdiff.json";
        $testDiff = __DIR__ . "/files/testdiff.json";
        
        $correctResult = file_get_contents($correctDiff);
        file_put_contents($testDiff, \GenDiff\Json\genDiff($before, $after));
        $diff = file_get_contents($testDiff);

        $this->assertEquals($diff, $correctResult);
    }

    public function testGenDiffJson2()
    {
        $before = __DIR__ . "/files/before2.json";
        $after = __DIR__ . "/files/after2.json";
        $correctDiff = __DIR__ . "/files/correctdiff2.json";
        $testDiff = __DIR__ . "/files/testdiff2.json";
        
        $correctResult = file_get_contents($correctDiff);
        file_put_contents($testDiff, \GenDiff\Json\genDiff($before, $after));
        $diff = file_get_contents($testDiff);

        $this->assertEquals($diff, $correctResult);
    }

    public function testGenDiffYaml()
    {
        $before = __DIR__ . "/files/before.yaml";
        $after = __DIR__ . "/files/after.yaml";
        $correctDiff = __DIR__ . "/files/correctdiff.yaml";
        $testDiff = __DIR__ . "/files/testdiff.yaml";
        
        $correctResult = file_get_contents($correctDiff);
        file_put_contents($testDiff, \GenDiff\Yaml\genDiff($before, $after));
        $diff = file_get_contents($testDiff);

        $this->assertEquals($diff, $correctResult);
    }
}
