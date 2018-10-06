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
        $correctResult = file_get_contents($correctDiff);
        $diff = \GenDiff\Differ\genDiff($before, $after);
        $this->assertEquals($diff, $correctResult);
    }

    public function testGenDiffJson2()
    {
        $before = __DIR__ . "/files/before2.json";
        $after = __DIR__ . "/files/after2.json";
        $correctDiff = __DIR__ . "/files/correctdiff2.json";
        $correctResult = file_get_contents($correctDiff);
        $diff = \GenDiff\Differ\genDiff($before, $after);
        $this->assertEquals($diff, $correctResult);
    }

    public function testGenDiffYaml()
    {
        $before = __DIR__ . "/files/before.yaml";
        $after = __DIR__ . "/files/after.yaml";
        $correctDiff = __DIR__ . "/files/correctdiff.yaml";
        $correctResult = file_get_contents($correctDiff);
        $diff = \GenDiff\Differ\genDiff($before, $after);
        $this->assertEquals($diff, $correctResult);
    }

    public function testGenDiffPlane()
    {
        $before = __DIR__ . "/files/before2.json";
        $after = __DIR__ . "/files/after2.json";
        $correctDiffPlain = __DIR__ . "/files/correctPlaneDiff.json";
        $correctResult = file_get_contents($correctDiffPlain);
        $diff = \GenDiff\Differ\genDiff($before, $after, 'plain');
        $this->assertEquals($diff, $correctResult);
    }

    public function testGenDiffToJson()
    {
        $before = __DIR__ . "/files/before2.json";
        $after = __DIR__ . "/files/after2.json";
        $correctDiffPlane = __DIR__ . "/files/correctJsonDiff.json";
        $testDiffJson = __DIR__ . "/files/testJsonDiff.json";
        $testDiffJson = \GenDiff\Differ\genDiff($before, $after, 'json');
        $this->assertJsonStringEqualsJsonFile(
            $correctDiffPlane,
            $testDiffJson
        );
    }
    
}
