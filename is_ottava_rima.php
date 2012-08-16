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

require __DIR__ . '/classes/CMUDict.class.php';

/**
 * @param string $stanza
 * @param string $delimiter
 * @param int $syllable_tolerance
 * @return bool
 */
function is_ottava_rima(
    $stanza,
    $delimiter = "\n",
    $syllable_tolerance = 2
) {
    $lines = extract_lines_from($stanza, $delimiter);

    // Rule 1.
    if (count($lines) !== 8) {
        return false;
    }

    // Rule 2.
    foreach ($lines as $line) {
        if (!is_iambic_pentameter($line, $syllable_tolerance)) {
            return false;
        }
    }

    // Rule 3.
    if (!is_abababcc_rhyme($lines)) {
        return false;
    }

    return true;
}

/**
 * @param string $stanza
 * @param string $delimiter
 * @return array
 */
function extract_lines_from($stanza, $delimiter = "\n") {
    // Separate the stanza into lines.
    return explode($delimiter, trim($stanza));
}

/**
 * @param string $line
 * @param int $syllable_tolerance
 * @return bool
 */
function is_iambic_pentameter($line, $syllable_tolerance) {
    $syllable_count = 0;
    $words = get_words_from($line);
    foreach ($words as $word) {
        $syllable_count += estimate_syllables($word);
    }
    $min_syllable_count = 10 - $syllable_tolerance;
    $max_syllable_count = 10 + $syllable_tolerance;
    return $syllable_count >= $min_syllable_count && $syllable_count <= $max_syllable_count;
}

/**
 * @param string $line
 * @return array
 */
function get_words_from($line) {
    $cleaned_line = trim(preg_replace("/[^A-Za-z' ]/", ' ', $line));
    return preg_split('/\s+/', $cleaned_line);
}

/**
 * @param string $word
 * @return int|null
 */
function estimate_syllables($word) {
    $syllable_count = count_arpabet_vowels($word);
    if ($syllable_count === null) {
        $syllable_count = count_english_vowels($word);
    }
    return $syllable_count;
}

/**
 * @param string $word
 * @return int|null
 */
function count_arpabet_vowels($word) {
    static $arpabet_vowels = array(
        'AO', 'AA', 'IY', 'UW', 'EH', // Monophthongs
        'IH', 'UH', 'AH', 'AX', 'AE',
        'EY', 'AY', 'OW', 'AW', 'OY', // Diphthongs
        'ER' // R-colored vowels
    );
    $cmu_dict = CMUDict::get();
    $phonemes = $cmu_dict->getPhonemes($word);
    if ($phonemes !== null) {
        $vowel_count = 0;
        foreach ($phonemes as $phoneme) {
            if (in_array($phoneme, $arpabet_vowels)) {
                $vowel_count++;
            }
        }
        return $vowel_count;
    } else {
        return null;
    }
}

/**
 * @param string $word
 * @return int
 */
function count_english_vowels($word) {
    static $english_vowels = array('A', 'E', 'I', 'O', 'U');
    $vowel_count = 0;
    $letters = str_split(strtoupper($word));
    foreach ($letters as $letter) {
        if (in_array($letter, $english_vowels)) {
            $vowel_count++;
        }
    }
    return $vowel_count;
}

/**
 * @param array $lines
 * @return bool
 */
function is_abababcc_rhyme($lines) {
    list($a1, $b1, $a2, $b2, $a3, $b3, $c1, $c2) = $lines;
    $a_rhymes = does_rhyme($a1, $a2) &&
        does_rhyme($a2, $a3) &&
        does_rhyme($a1, $a3);
    $b_rhymes = does_rhyme($b1, $b2) &&
        does_rhyme($b2, $b3) &&
        does_rhyme($b1, $b3);
    $c_rhymes = does_rhyme($c1, $c2);
    return $a_rhymes && $b_rhymes && $c_rhymes;
}

/**
 * @param string $line1
 * @param string $line2
 * @return bool
 */
function does_rhyme($line1, $line2) {
    $words1 = get_words_from($line1);
    $last_word1 = $words1[count($words1) - 1];
    $words2 = get_words_from($line2);
    $last_word2 = $words2[count($words2) - 1];

    $words_found = true;
    $cmu_dict = CMUDict::get();
    $phonemes1 = $cmu_dict->getPhonemes($last_word1);
    if ($phonemes1 === null) {
        $words_found = false;
    }
    $phonemes2 = $cmu_dict->getPhonemes($last_word2);
    if ($phonemes2 === null) {
        $words_found = false;
    }

    if ($words_found) {
        $last_syllable1 = get_last_syllable_of($phonemes1);
        $last_syllable2 = get_last_syllable_of($phonemes2);
        $rhymes = $last_syllable1 === $last_syllable2;
    } else {
        $metaphone1 = metaphone($last_word1);
        $metaphone2 = metaphone($last_word2);
        $rhymes = substr($metaphone1, -1) === substr($metaphone2, -1);
    }

    if (!$rhymes) {
        error_log("$last_word1 and $last_word2 don't rhyme.");
    }

    return $rhymes;
}

/**
 * @param array $phonemes
 * @return string
 */
function get_last_syllable_of($phonemes) {
    static $arpabet_vowels = array(
        'AO', 'AA', 'IY', 'UW', 'EH', // Monophthongs
        'IH', 'UH', 'AH', 'AX', 'AE',
        'EY', 'AY', 'OW', 'AW', 'OY', // Diphthongs
        'ER' // R-colored vowels
    );

    $reversed_syllable_phonemes = array();
    foreach (array_reverse($phonemes) as $phoneme) {
        $reversed_syllable_phonemes[] = $phoneme;
        if (in_array($phoneme, $arpabet_vowels)) {
            break;
        }
    }
    return implode('', array_reverse($reversed_syllable_phonemes));
}


