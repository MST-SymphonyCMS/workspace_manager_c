<?php

namespace workspace_manager_c;

class Helpers
{
    public static function capitalizeWords($string)
    {
        return ucwords(str_replace('-', ' ', $string));
    }
}