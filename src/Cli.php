<?php
namespace GenDiff\Cli;

use Docopt;

function run()
{
    $doc = <<<DOC
    
Generate diff
    
Usage:
    gendiff (-h|--help)
    gendiff [--format <fmt>] <firstFile> <secondFile>
    
Options:
    -h --help                     Show this screen
    --format <fmt>                Report format [default: pretty]
DOC;
    
    $result = Docopt::handle($doc, array('version'=>'1.0.0'));
    foreach ($result as $k=>$v) {
        echo $k.': '.json_encode($v).PHP_EOL;
    }
}