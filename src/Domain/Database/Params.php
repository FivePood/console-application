<?php

namespace Services\Domain\Database;

use Symfony\Component\Yaml\Yaml;

class Params
{
    const DEFAULT_DB = 1;
    const REDIS = 2;

    const PATH_CONF = __DIR__ . "/../../../config/";

    public static function get(int $typeDB): array
    {
        return match ($typeDB) {
            self::DEFAULT_DB => YAML::parseFile(filename: self::PATH_CONF . "default_db.yml"),
            self::REDIS => YAML::parseFile(filename: self::PATH_CONF . "redis_db.yml")
        };
    }
}
