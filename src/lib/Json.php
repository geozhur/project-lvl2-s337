<?php
namespace GenDiff\Json;

use function \GenDiff\Common\encode as encode;

function getJSONContents($pathToFile)
{
    return json_decode(file_get_contents($pathToFile), true);
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
                $acc[] = sprintf("    %s: %s", $item, encode($content1[$item]));
            } else {
                $acc[] = sprintf("  + %s: %s", $item, encode($content2[$item]));
                $acc[] = sprintf("  - %s: %s", $item, encode($content1[$item]));
            }
        } else {
            $acc[] = sprintf("  - %s: %s", $item, encode($content1[$item]));
        }

        return $acc;
    }, []);

    $result = implode("\n", array_reduce($contentKeys2, function ($acc, $item) use ($content1, $content2) {
        if (!array_key_exists($item, $content1)) {
            $acc[] = sprintf("  + %s: %s", $item, encode($content2[$item]));
        }
        return $acc;
    }, $result1));

    return "{\n$result\n}";
}
