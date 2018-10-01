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

function getJSONContents($pathToFile)
{
    return json_decode(file_get_contents($pathToFile), true);
}

function encodeJSON($data)
{
    switch (gettype($data)) {
        case 'NULL':
            return 'null';
        case 'boolean':
            return ($data ? 'true' : 'false');
        default:
            return $data;
    }
}


function genDiff($pathToFile1, $pathToFile2)
{
    $content1 = getJSONContents($pathToFile1);
    $contentKeys1 = array_keys($content1);
    $content2 = getJSONContents($pathToFile2);
    $contentKeys2 = array_keys($content2);

    $result1 = array_reduce($contentKeys1, function ($acc, $item) use ($content1, $content2) {
        if (array_key_exists($item, $content2)) {
            if ($content1[$item] == $content2[$item]) {
                $acc[] = sprintf("   %s: %s", $item, encodeJSON($content1[$item]));
            } else {
                $acc[] = sprintf(" + %s: %s", $item, encodeJSON($content2[$item]));
                $acc[] = sprintf(" - %s: %s", $item, encodeJSON($content1[$item]));
            }
        } else {
            $acc[] = sprintf(" - %s: %s", $item, encodeJSON($content1[$item]));
        }

        return $acc;
    }, []);

    $result = implode("\n", array_reduce($contentKeys2, function ($acc, $item) use ($content1, $content2) {
        if (!array_key_exists($item, $content1)) {
            $acc[] = sprintf(" + %s: %s", $item, encodeJSON($content2[$item]));
        }
        return $acc;
    }, $result1));

    return "{\n$result\n}";
}

function run()
{
    $result = Docopt::handle(HELP, array('version' => '2.0.0'));

    line();
    print_r(genDiff($result->args['<firstFile>'], $result->args['<secondFile>']));
    line();
}
