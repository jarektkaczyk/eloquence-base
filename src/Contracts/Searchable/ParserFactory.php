<?php

namespace Sofa\Eloquence\Contracts\Searchable;

interface ParserFactory
{
    /**
     * Create new parser instance.
     *
     * @param  int $weight
     * @param  string  $wildcard
     * @return Parser
     */
    public static function make($weight = 1, $wildcard = '*');
}
