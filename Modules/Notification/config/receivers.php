<?php

/**
 *JSON: database/seeders/data/receivers.json
 */
$jsonPath = __DIR__.'/../database/seeders/data/receivers.json';

if (file_exists($jsonPath)) {
    return json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
}

return [];
