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
    try {
        $result = Docopt::handle(HELP, array('version' => '4.0.0'));

        $firstFile = $result->args['<firstFile>'];
        $secondFile = $result->args['<secondFile>'];
        $format = $result->args['--format'];

        line();
        line(Differ\genDiff($firstFile, $secondFile, $format));
        line();
    } catch (\Exception $e) {
        echo "Message: " . $e->getMessage();
    }
}
