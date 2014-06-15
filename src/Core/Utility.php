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

    public static function cutoutFilterFactory($open, $close, CliApp $cliTerm)
    {
        return function($c) use ($cliTerm, $open, $close) {

            $startPos = stripos($c, $open);

            while($startPos !== false) {
                $endPos = stripos($c, $close, $startPos) + strlen($close);

                $c = substr($c, 0, $startPos) . substr($c, $endPos);

                $cliTerm->out(
                    sprintf(
                        "Filtering %s",
                        $cliTerm->switchColor('red') . str_replace(array("\n", "\r", "\t", '    ', '   ', '  '), ' ', $open) . $cliTerm->switchColor('green')
                    ), array('green', 'bold')
                );

                $startPos = stripos($c, $open);
            }

            return $c;
        };
    }

    public static function replaceFilterFactory($original, $replacement, CliApp $cliTerm)
    {
        return function ($c) use ($cliTerm, $original, $replacement) {
            $c = preg_replace($original, $replacement, $c, -1, $count);

            if ($count > 0) {
                $cliTerm->out(
                    sprintf(
                        "Filtering %s",
                        $cliTerm->switchColor('red') . str_replace(array("\n", "\r", "\t", '    ', '   ', '  '), ' ', $original) . $cliTerm->switchColor('green')
                    ), array('green', 'bold')
                );
            }

            return $c;
        };
    }

    /**
     * Changes a string to make sure that could be the name of a file on the OS filesystem
     *
     * @static
     *
     * @param string $string
     * @param string $replacement
     *
     * @return string
     */
    public static function OSSafeString($string, $replacement = ' ')
    {
        //return preg_replace('/[^A-Za-z0-9]/', '_', $string);
        return preg_replace(
            '/[^\x21\x27\x28\x29\x2B\x2C\x2D\x2E\x30\x31\x32\x33\x34\x35\x36\x37\x38\x39\x40\x41\x42\x43\x44\x45\x46\x47\x48\x49\x4A\x4B\x4C\x4D\x4E\x4F\x50\x51\x52\x53\x54\x55\x56\x57\x58\x59\x5A\x5B\x5D\x5F\x60\x61\x62\x63\x64\x65\x66\x67\x68\x69\x6A\x6B\x6C\x6D\x6E\x6F\x70\x71\x72\x73\x74\x75\x76\x77\x78\x79\x7A]/i',
            $replacement,
            $string
        );
    }
}