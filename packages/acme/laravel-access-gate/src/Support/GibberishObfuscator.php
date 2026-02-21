<?php

namespace Acme\AccessGate\Support;

use Illuminate\Support\HtmlString;

class GibberishObfuscator
{
    /**
     * Replace HTML text content with length-preserving gibberish (keeps structure).
     */
    public function obfuscate(string $html): HtmlString
    {
        $obfuscated = preg_replace_callback(
            '/>([^<]+)</i',
            function (array $matches): string {
                $text = $matches[1];
                $gibberish = '';

                for ($i = 0; $i < strlen($text); $i++) {
                    $char = $text[$i];

                    if (ctype_space($char)) {
                        $gibberish .= $char;
                    } elseif (ctype_alpha($char)) {
                        $gibberish .= ctype_upper($char)
                            ? chr(random_int(65, 90))
                            : chr(random_int(97, 122));
                    } elseif (ctype_digit($char)) {
                        $gibberish .= (string) random_int(0, 9);
                    } else {
                        $gibberish .= random_int(0, 9) < 5
                            ? (string) random_int(0, 9)
                            : chr(random_int(97, 122));
                    }
                }

                return '>' . $gibberish . '<';
            },
            $html
        );

        return new HtmlString($obfuscated ?? $html);
    }
}
