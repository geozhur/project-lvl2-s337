<?php
namespace Formatters\Json;

use function GenDiff\Differ\encode as encode;
use function GenDiff\Differ\encode as stringify;

/*
function render($ast)
{
    return json_encode($ast, JSON_PRETTY_PRINT);
}*/

function render($ast, $spaces = '')
{
    $result = array_map(function ($item) use ($spaces) {
        $itemArr = (array)$item;
        $itemKeys =  array_keys($itemArr);
        $type = $item->type;

        $node = array_map(function ($elem) use ($spaces, $type, $itemArr) {
            $quotes = true;
            $key = encode($elem, $quotes);
            if ($elem ==='children' && $type == 'node') {
                $tree = render($itemArr[$elem], "      {$spaces}");
                return "    {$spaces}{$key}: {$tree}";
            }
            $value = stringify($itemArr[$elem], "    {$spaces}", $quotes);
            if (($elem === 'newValue' && $type == 'added') ||
                ($elem === 'oldValue' && $type == 'removed')) {
                return "    {$spaces}{$key}: {$value}";
            }
            return "    {$spaces}{$key}: {$value}";
        }, $itemKeys);

        $strNode = implode(",\n", $node);
        return "  {$spaces}{\n{$strNode}\n  {$spaces}}";
    }, $ast);

    $strResult = implode(",\n", array_merge($result));
    return "[\n{$strResult}\n{$spaces}]";
}
