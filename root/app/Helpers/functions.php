<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (!function_exists('getRandomFakeIdentity')) {
    function getRandomFakeIdentity()
    {
        $data = Cache::rememberForever('data_fake', function () {
            $json = Storage::get('data/data_fake.json');
            return json_decode($json, true);
        });

        return $data[array_rand($data)];
    }
}
