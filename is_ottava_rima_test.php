<?php
/*
 * Copyright 2012 Cameron McKay
 *
 * This file is part of IsOttavaRima.
 *
 * IsOttavaRima is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * IsOttavaRima is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with IsOttavaRima.
 * If not, see http://www.gnu.org/licenses/.
 */

require_once __DIR__ . '/is_ottava_rima.php';

/**
 * @param string $path
 * @return array
 */
function read_poem_file($path) {
    $stanzas = array();
    $handle = fopen($path, 'r');
    if ($handle) {
        $stanza = '';
        $i = 0;
        while (($buffer = fgets($handle, 4096)) !== false) {
            if (starts_with('---', $buffer)) {
                continue;
            }
            $stanza .= $buffer;
            $i++;
            if ($i % 8 === 0) {
                $stanzas[] = rtrim($stanza);
                $stanza = '';
            }
        }
        if (!feof($handle)) {
            error_log('Unexpected fgets() fail.');
        }
        fclose($handle);
    }

    return $stanzas;
}

/**
 * Returns true if $str starts with $prefix, false otherwise.
 *
 * @param $prefix
 * @param $str
 * @return bool
 */
function starts_with($prefix, $str) {
    return !strncmp($str, $prefix, strlen($prefix));
}

$ottava_rima_stanza_count = 0;
$ottava_rima_detected_stanza_count = 0;

// Get a list of poem files.
foreach (glob(__DIR__ . '/ottava-rima-poems/*.txt') as $poem_path) {
    $filename = pathinfo($poem_path, PATHINFO_BASENAME);
    echo "Reading ottava rima poems from '$filename'..." . PHP_EOL;
    $stanzas = read_poem_file($poem_path);
    echo 'Read ' . count($stanzas) . ' stanza(s).' . PHP_EOL;
    foreach ($stanzas as $i => $stanza) {
        $ottava_rima_stanza_count++;
        echo 'Stanza ' . str_pad($i, 2, '0', STR_PAD_LEFT) . ': ';
        if (is_ottava_rima($stanza)) {
            $ottava_rima_detected_stanza_count++;
            echo 'ottava rima';
        } else {
            echo 'not ottava rima';
        }
        echo PHP_EOL;
    }
}

// Print results.
$ottava_rima_poem_percentage = number_format($ottava_rima_detected_stanza_count / $ottava_rima_stanza_count * 100.0, 2);
echo "$ottava_rima_detected_stanza_count / $ottava_rima_stanza_count ottava rima stanzas detected ($ottava_rima_poem_percentage%)" . PHP_EOL;

$garbage_stanza_count = 0;
$garbage_detected_stanza_count = 0;

// Get a list of poem files.
foreach (glob(__DIR__ . '/garbage-poems/*.txt') as $poem_path) {
    $filename = pathinfo($poem_path, PATHINFO_BASENAME);
    echo "Reading garbage poems from '$filename'..." . PHP_EOL;
    $stanzas = read_poem_file($poem_path);
    echo 'Read ' . count($stanzas) . ' stanza(s).' . PHP_EOL;
    foreach ($stanzas as $i => $stanza) {
        $garbage_stanza_count++;
        echo 'Stanza ' . str_pad($i, 2, '0', STR_PAD_LEFT) . ': ';
        if (is_ottava_rima($stanza)) {
            $garbage_detected_stanza_count++;
            echo 'ottava rima';
        } else {
            echo 'not ottava rima';
        }
        echo PHP_EOL;
    }
}

// Print results.
$garbage_poem_percentage = number_format($garbage_detected_stanza_count / $garbage_stanza_count * 100.0, 2);
echo "$garbage_detected_stanza_count / $garbage_stanza_count ottava rima stanzas detected in garbage ($garbage_poem_percentage%)" . PHP_EOL;