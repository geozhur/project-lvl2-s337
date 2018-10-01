<?php
namespace GenDiff\Cli;

use Docopt;

const HELP = <<<DOC
    
Generate diff
    
Usage:
    gendiff (-h|--help)
    gendiff [--format <fmt>] <firstFile> <secondFile>
    
Options:
    -h --help                     Show this screen
    --format <fmt>                Report format [default: pretty]
DOC;


function run()
{
    $result = Docopt::handle(HELP, array('version' => '1.0.0'));
    array_walk($result, function ($item, $key) {
        echo $key.': '.json_encode($item).PHP_EOL;
    });
}
