<?php

namespace StoreKeeper\StoreKeeper\Helper;

class Base36Coder
{
    /**
     * @param string $string
     * @return string
     */
    public function encode(string$string, string $prefix = ''): string
    {
        return $prefix.$this->strBaseConvert(bin2hex($string), 16, 36);
    }

    /**
     * @param string$string
     * @return string
     */
    public  function decode(string$string, string $prefix = ''): string
    {
        return hex2bin($this->strBaseConvert(str_replace($prefix, '', $string), 36, 16));
    }

    /**
     * @param string$str
     * @param int $frombase
     * @param int $tobase
     * @return int|string
     */
    protected  function strBaseConvert(string$str, int$frombase = 10, int$tobase = 36)
    {
        $str = trim($str);
        if (10 != intval($frombase)) {
            $len = strlen($str);
            $q = 0;
            for ($i = 0; $i < $len; ++$i) {
                $r = base_convert($str[$i], $frombase, 10);
                $q = bcadd(bcmul($q, $frombase), $r);
            }
        } else {
            $q = $str;
        }
        if (10 != intval($tobase)) {
            $s = '';
            while (bccomp($q, '0', 0) > 0) {
                $r = intval(bcmod($q, $tobase));
                $s = base_convert($r, 10, $tobase).$s;
                $q = bcdiv($q, $tobase, 0);
            }
        } else {
            $s = $q;
        }

        return $s;
    }
}