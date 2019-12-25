<?php

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('floor_dec')) {
    function floor_dec($value, int $decimal = 2)
    {
        $zero_word = '1';
        for($i=0; $i < $decimal; $i++){
            $zero_word.='0';
        }
        $zero = (int) $zero_word;

        return floor($value * $zero) / $zero;
    }
}