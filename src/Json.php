<?php
namespace Formatters\Json;

function render($ast)
{
    $getJsonIter = function ($begin, $ast, $end, $spaces) use (&$getJsonIter) {
        $result = array_map(function ($item) use ($begin, $end, $spaces, &$getJsonIter) {
            $itemArr = (array)$item;
            $itemKeys =  array_keys($itemArr);
                $node = array_map(function ($elem) use ($spaces, $itemArr, $begin, $end, &$getJsonIter) {
                    if ($elem ==='value' && is_array($itemArr[$elem])) {
                        return "{$spaces}\"{$elem}\": [{ \"0\": \"{$itemArr[$elem][0]}\"},".
                                "{ \"0\": \"{$itemArr[$elem][1]}\"}]";
                    }
                    if ($elem ==='children' && is_array($itemArr[$elem])) {
                        $tree = $getJsonIter($begin, $itemArr[$elem], "    {$end}", "    {$spaces}");
                        return "{$spaces}\"{$elem}\": {$tree}";
                    }
                    return "{$spaces}\"{$elem}\": \"{$itemArr[$elem]}\"";
                }, $itemKeys);
            $strNode = implode(",\n", $node);
            return "{$spaces}{\n{$strNode}\n{$spaces}}";
        }, $ast);
     
        $strResult = implode(",\n", array_merge($result));
        return "{$begin}\n{$strResult}\n{$end}";
    };
    return $getJsonIter('[', $ast, ']', "   ");
}
