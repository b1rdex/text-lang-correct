<?php /** @noinspection UnqualifiedReferenceInspection */

namespace B1rdex\Text\Util;

/**
 * @internal
 *
 * PHP5 UTF-8 is a UTF-8 aware library of functions mirroring PHP's own string functions.
 *
 * The powerful solution/contribution for UTF-8 support in your framework/CMS, written on PHP.
 * This library is advance of http://sourceforge.net/projects/phputf8 (last updated in 2007).
 *
 * UTF-8 support in PHP 5.
 *
 * Features and benefits of using this class
 *   * Compatibility with the interface standard PHP functions that deal with single-byte encodings
 *   * Ability to work without PHP extensions ICONV and MBSTRING, if any, that are actively used!
 *   * Useful features are missing from the ICONV and MBSTRING
 *   * The methods that take and return a string, are able to take and return null (useful for selects from a database)
 *   * Several methods are able to process arrays recursively
 *   * A single interface and encapsulation (you can inherit and override)
 *   * High performance, reliability and quality code
 *   * PHP> = 5.3.x
 *
 * In Russian:
 *
 * Поддержка UTF-8 в PHP 5.
 *
 * Возможности и преимущества использования этого класса
 *   * Совместимость с интерфейсом стандартных PHP функций, работающих с однобайтовыми кодировками
 *   * Возможность работы без PHP расширений ICONV и MBSTRING, если они есть, то активно используются!
 *   * Полезные функции, отсутствующие в ICONV и MBSTRING
 *   * Методы, которые принимают и возвращают строку, умеют принимать и возвращать null (удобно при выборках значений из базы данных)
 *   * Несколько методов умеют обрабатывать массивы рекурсивно
 *   * Единый интерфейс и инкапсуляция (можно унаследоваться и переопределить методы)
 *   * Высокая производительность, надёжность и качественный код
 *   * PHP >= 5.3.x
 *
 * Example:
 *   $s = 'Hello, Привет';
 *
 * UTF-8 encoding scheme:
 *   2^7   0x00000000 — 0x0000007F  0xxxxxxx
 *   2^11  0x00000080 — 0x000007FF  110xxxxx 10xxxxxx
 *   2^16  0x00000800 — 0x0000FFFF  1110xxxx 10xxxxxx 10xxxxxx
 *   2^21  0x00010000 — 0x001FFFFF  11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
 *   1-4 bytes length: 2^7 + 2^11 + 2^16 + 2^21 = 2 164 864
 *
 * If I was a owner of the world, I would leave only 2 encoding: UTF-8 and UTF-32 ;-)
 *
 * Useful links
 *   http://ru.wikipedia.org/wiki/UTF8
 *   http://www.madore.org/~david/misc/unitest/   A Unicode Test Page
 *   http://www.unicode.org/
 *   http://www.unicode.org/reports/
 *   http://www.unicode.org/reports/tr10/      Unicode Collation Algorithm
 *   http://www.unicode.org/Public/UCA/6.0.0/  Unicode Collation Algorithm
 *   http://www.unicode.org/reports/tr6/       A Standard Compression Scheme for Unicode
 *   http://www.fileformat.info/info/unicode/char/search.htm  Unicode Character Search
 *
 * @link     http://code.google.com/p/php5-utf8/
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat
 * @version  2.2.2
 */
class UTF8
{
    #REPLACEMENT CHARACTER (for broken char)
    const REPLACEMENT_CHAR = "\xEF\xBF\xBD"; #U+FFFD

    /**
     * Combining diactrical marks (Unicode 5.1).
     *
     * For example, russian letters in composed form: "Ё" (U+0401), "Й" (U+0419),
     * decomposed form: (U+0415 U+0308), (U+0418 U+0306)
     *
     * @link http://www.unicode.org/charts/PDF/U0300.pdf
     * @link http://www.unicode.org/charts/PDF/U1DC0.pdf
     * @link http://www.unicode.org/charts/PDF/UFE20.pdf
     * @var  string
     */
    #public static $diactrical_re = '\p{M}'; #alternative, but only with /u flag
    public static $diactrical_re = '  \xcc[\x80-\xb9]|\xcd[\x80-\xaf]  #UNICODE range: U+0300 — U+036F (for letters)
                                    | \xe2\x83[\x90-\xbf]              #UNICODE range: U+20D0 — U+20FF (for symbols)
                                    | \xe1\xb7[\x80-\xbf]              #UNICODE range: U+1DC0 — U+1DFF (supplement)
                                    | \xef\xb8[\xa0-\xaf]              #UNICODE range: U+FE20 — U+FE2F (combining half marks)
                                   ';


    #calling the methods of this class only statically!
    private function __construct()
    {
    }

    /**
     * Remove combining diactrical marks, with possibility of the restore
     * Удаляет диакритические знаки в тексте, с возможностью восстановления (опция)
     *
     * @param   string|null $s
     * @param   array|null  $additional_chars for example: "\xc2\xad"  #soft hyphen = discretionary hyphen
     * @param   bool        $is_can_restored
     * @param   array|null  $restore_table
     *
     * @return  string|bool|null  Returns FALSE if error occurred
     */
    public static function diactrical_remove(
        $s,
        $additional_chars = null,
        $is_can_restored = false,
        &$restore_table = null
    ) {
        if (!ReflectionTypeHint::isValid()) {
            return false;
        }
        if (is_null($s)) {
            return $s;
        }

        if ($additional_chars) {
            foreach ($additional_chars as $k => &$v) {
                $v = preg_quote($v, '/');
            }
            $re = '/((?>' . self::$diactrical_re . '|' . implode('|', $additional_chars) . ')+)/sxSX';
        } else {
            $re = '/((?>' . self::$diactrical_re . ')+)/sxSX';
        }
        if (!$is_can_restored) {
            return preg_replace($re, '', $s);
        }

        $restore_table = [];
        $a = preg_split($re, $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $c = count($a);
        if ($c === 1) {
            return $s;
        }
        $pos = 0;
        $s2 = '';
        for ($i = 0; $i < $c - 1; $i += 2) {
            $s2 .= $a[$i];
            #запоминаем символьные (не байтовые!) позиции
            $pos += mb_strlen($a[$i], 'utf-8');
            $restore_table['offsets'][$pos] = $a[$i + 1];
        }
        $restore_table['length'] = $pos + mb_strlen(end($a), 'utf-8');
        return $s2 . end($a);
    }

    /**
     * Restore combining diactrical marks, removed by self::diactrical_remove()
     * In Russian:
     * Восстанавливает диакритические знаки в тексте, при условии, что их символьные позиции и кол-во символов не изменились!
     *
     * @see     self::diactrical_remove()
     *
     * @param   string|null $s
     * @param   array       $restore_table
     *
     * @return  string|bool|null  Returns FALSE if error occurred (broken $restore_table)
     */
    public static function diactrical_restore($s, array $restore_table)
    {
        if (!ReflectionTypeHint::isValid()) {
            return false;
        }
        if (is_null($s)) {
            return $s;
        }

        if (!$restore_table) {
            return $s;
        }
        if (
            !is_int(@$restore_table['length']) || !is_array(@$restore_table['offsets'])
            || $restore_table['length'] !== mb_strlen($s, 'utf-8')
        ) {
            return false;
        }
        $a = [];
        $length = $offset = 0;
        $s2 = '';
        foreach ($restore_table['offsets'] as $pos => $diactricals) {
            $length = $pos - $offset;
            $s2 .= self::substr($s, $offset, $length) . $diactricals;
            $offset = $pos;
        }
        return $s2 . self::substr($s, $offset, strlen($s));
    }

    /**
     * @deprecated
     * @see mb_substr()
     *
     * Implementation substr() function for UTF-8 encoding string.
     *
     * @link     http://www.w3.org/International/questions/qa-forms-utf-8.html
     *
     * @param    string    $s
     * @param    int      $offset
     * @param    int|null $length
     *
     * @return   string|false             Returns FALSE if error occurred
     */
    public static function substr($s, $offset, $length = null)
    {
        if ($length === null) {
            $length = mb_strlen($s, 'utf-8');
        }
        return mb_substr($s, $offset, $length, 'utf-8');
    }

}
