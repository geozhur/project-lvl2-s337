<?php
namespace GenDiff;

use Docopt;
use function \cli\line;

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
    $result = Docopt::handle(HELP, array('version' => '2.0.0'));

    $firstFile = $result->args['<firstFile>'];
    $secondFile = $result->args['<secondFile>'];
    $firstFileExt = pathinfo($firstFile, PATHINFO_EXTENSION);
    $secondFileExt = pathinfo($secondFile, PATHINFO_EXTENSION);
    line();
    switch ($firstFileExt) {
        case 'json':
            line(Json\genDiff($firstFile, $secondFile));
            break;
        case 'yaml':
            line(Yaml\genDiff($firstFile, $secondFile));
            break;
        default:
    }
    line();
}
