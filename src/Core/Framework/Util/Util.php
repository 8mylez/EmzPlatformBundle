<?php

namespace Emz\PlatformBundle\Core\Framework\Util;

class Util
{
    public static function iteratorToArray(iterable $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }
}
