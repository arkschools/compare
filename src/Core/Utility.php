<?php

namespace Ark\Compare\Core;


class Utility {

    /**
     * Translates a camel case string into a string with
     * underscores (e.g. firstName -> first_name)
     *
     * @param string $str String in camel case format
     * @return string $str Translated into underscore format
     */
    public static function fromCamelCase($str) {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Translates a string with underscores
     * into camel case (e.g. first_name -> firstName)
     *
     * @param string $str String in underscore format
     * @param bool $capitalise_first_char If true, capitalise the first char in $str
     * @return string $str translated into camel caps
     */
    public static function toCamelCase($str, $capitalise_first_char = false) {
        if($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    public static function cutoutFilterFactory($open, $close, $cliTerm) {
        return function($c) use ($cliTerm, $open, $close){

            $startPos = stripos($c, $open);

            while($startPos !== FALSE) {
                $cLen = strlen($c);
                $endPos = stripos($c, $close, $startPos) + strlen($close);

                $c = substr($c, 0, $startPos) . substr($c, $endPos);

                $cliTerm->out(
                    sprintf(
                        "Filtering %s",
                        $cliTerm->switchColor('red') . $open . $cliTerm->switchColor('green')
                    ), array('green', 'bold')
                );

                $startPos = stripos($c, $open);
            }
            return $c;
        };
    }
}