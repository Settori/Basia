<?php

declare(strict_types=1);

namespace App\Security;
trait ToArrayTrait
{
    public function toArray(): array
    {
        $data = [];

        foreach(get_object_vars($this) as $key => $value) {
            $data[$key] = is_object($value) ? $this->parseObject($value) : $value;
        }

        return $data;
    }

    private function parseObject(object $object): array
    {
        $data = [];

        foreach(get_object_vars($object) as $key => $value) {
            $data[$key] = is_object($value) ? $this->parseObject($value) : $value;
        }

        return $data;
    }
}