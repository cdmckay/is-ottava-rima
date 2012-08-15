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

class CMUDict {

    private $keys = array();
    private $values = array();
    private $count = 0;

    private function __construct() {
        $handle = fopen(__DIR__ . '/cmu-dict.txt', 'r');
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                // Handle comments.
                if (self::starts_with(';;;', $buffer)) {
                    continue;
                }
                list($word, $imploded_phonemes) = explode('  ', rtrim($buffer));
                $phonemes = explode(' ', $imploded_phonemes);
                $modified_phonemes = array();
                foreach ($phonemes as $phoneme) {
                    $modified_phoneme = $phoneme;
                    if (strlen($modified_phoneme) > 2) {
                        // Remove stress from phonemes, as poems can play with stress a lot.
                        $modified_phoneme = substr($modified_phoneme, 0, 2);
                    }
                    $modified_phonemes[] = $modified_phoneme;
                }
                // Ignoring alternate pronunciations for now.
                if (!self::ends_with(')', $word)) {
                    $this->values[$word] = $modified_phonemes;
                }
            }
            if (!feof($handle)) {
                throw new RuntimeException("Unexpected fgets() fail.\n");
            }
            fclose($handle);
        }
        $this->keys = array_keys($this->values);
        $this->count = count($this->values);
    }

    // No cloning!
    function __clone() {
    }

    static function get() {
        static $instance = null;
        if ($instance === null) {
            $instance = new CMUDict();
        }
        return $instance;
    }

    function getPhonemes($word) {
        $uppercased_word = strtoupper($word);
        $result = $this->values[$uppercased_word];
        if ($result !== null) {
            return $result;
        }

        // If it ends with 'd, replace with ed.
        if (self::ends_with("'D", $uppercased_word)) {
            return $this->getPhonemes(substr($uppercased_word, 0, -2) . 'ED');
        }

        // If it has a - or ' in it, try splitting it and returning the words separate.
        $split_words = null;
        if (self::contains('-', $uppercased_word)) {
            $split_words = explode('-', $uppercased_word);
        } else if (self::contains("'", $uppercased_word) && !self::ends_with("'S", $uppercased_word)) {
            $split_words = explode("'", $uppercased_word);
        }
        if (!empty($split_words)) {
            $merged_phonemes = array();
            foreach ($split_words as $split_word) {
                $phonemes = $this->getPhonemes($split_word);
                if ($phonemes === null) {
                    return null;
                }
                $merged_phonemes = array_merge($merged_phonemes, $phonemes);
            }
            return $merged_phonemes;
        }

        return null;
    }

    function getRandomWord() {
        $word = null;
        $suitable = false;
        while (!$suitable) {
            $word = strtolower($this->keys[mt_rand(0, $this->count - 1)]);
            // Ensure it starts with a letter and not punctuation.
            if (preg_match('/^[a-z]/', $word)) {
                $suitable = true;
            }
        }
        return $word;
    }

    /**
     * Returns true if the $value is a substring of $str, false otherwise.
     *
     * @param $value
     * @param $str
     * @return bool
     */
    private static function contains($value, $str) {
        return strstr($str, $value) !== false;
    }

    /**
     * Returns true if $str starts with $prefix, false otherwise.
     *
     * @param $prefix
     * @param $str
     * @return bool
     */
    private static function starts_with($prefix, $str) {
        return !strncmp($str, $prefix, strlen($prefix));
    }

    /**
     * Returns true if $str ends with $suffix, false otherwise.
     *
     * @param $suffix
     * @param $str
     * @return bool
     */
    private static function ends_with($suffix, $str) {
        return substr($str, -strlen($suffix)) === $suffix;
    }

    /**
     * Returns the last element of a countable (like an array).
     *
     * @param $countable
     * @return mixed
     */
    private static function last($countable) {
        return $countable[count($countable) - 1];
    }

}
