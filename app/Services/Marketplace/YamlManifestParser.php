<?php

namespace App\Services\Marketplace;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class YamlManifestParser
{
    /**
     * @return array<string, mixed>
     */
    public static function parse(string $yaml): array
    {
        try {
            $data = Yaml::parse($yaml);
        } catch (ParseException $e) {
            throw new \InvalidArgumentException('Invalid YAML: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($data)) {
            throw new \InvalidArgumentException('YAML manifest must parse to a mapping (object).');
        }

        return $data;
    }
}
