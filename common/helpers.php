<?php

use Illuminate\Support\Str;

if (! function_exists('slugify')) {
    /**
     * @param  string  $title
     * @param  string  $separator
     * @return string
     */
    function slugify($title, $separator = '-')
    {
        $slugified = Str::slug($title, $separator);

        if ( ! $slugified) {
            $slugified = strtolower(preg_replace('/[\s_]+/', $separator, $title));
        }

        return $slugified;
    }
}
