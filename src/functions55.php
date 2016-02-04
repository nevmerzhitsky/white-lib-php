<?php
/**
 * Here is code only for PHP 5.5.0+ version.
 */

/**
 * @param string $path
 * @param string $mode
 * @param boolean $trimLine
 * @param boolean $skipEmptyLines
 * @return Generator
 */
function textFileLineIterator ($path, $mode = 'r', $trimLine = true, $skipEmptyLines = true) {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    if (($fp = fopen($path, $mode)) === false) {
        throw new ApplicationException('Cannot open file');
    }

    while (!feof($fp)) {
        $line = fgets($fp);

        if (!empty($trimLine)) {
            $line = trim($line);
        }

        if (empty($line) && !empty($skipEmptyLines)) {
            continue;
        }

        yield $line;
    }

    fclose($fp);
}
