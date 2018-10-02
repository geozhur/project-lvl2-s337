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
    $result = Docopt::handle(HELP, array('version' => '4.0.0'));

    $firstFile = $result->args['<firstFile>'];
    $secondFile = $result->args['<secondFile>'];

    line();
    line(Common\checkFileExtAndDiff($firstFile, $secondFile));
    line();
}
