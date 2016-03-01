<?php

namespace BODM\Helper;

class Helper
{
    public static function prefixKeys($array, $prefix): array
    {
        $prefixedArray = [];
        foreach($array as $key=>$value) {
            $prefixedKey = $prefix.$key;
            $prefixedArray[$prefixedKey] = $value;
        }
        return $prefixedArray;
    }
}
