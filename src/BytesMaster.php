<?php

class BytesMaster {

    static function unxor_dword ($data, $datan, $dword) {
        $ret = '';
        $k = array(
            static::_HIBYTE(static::_HIWORD($dword)),
            static::_LOBYTE(static::_HIWORD($dword)),
            static::_HIBYTE(static::_LOWORD($dword)),
            static::_LOBYTE(static::_LOWORD($dword))
        );
        for ($i = 0, $j = 0; $i < $datan; $i++) {
            $ret .= chr(ord($data[$i]) ^ ($i + $k[$j]));
            if ($j == 3) {
                $j = 0;
            } else {
                $j++;
            }
        }
        return $ret;
    }

    static private function _LOWORD ($dword) {
        return $dword & 0xffff;
    }

    static private function _HIWORD ($dword) {
        return ($dword >> 16) & 0xffff;
    }

    static private function _LOBYTE ($word) {
        return (int) ($word & 0xff);
    }

    static private function _HIBYTE ($word) {
        return (int) (($word >> 8) & 0xff);
    }
}
