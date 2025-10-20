<?php

class StringService {
    public static function analyze($value) {
        $length = mb_strlen($value);
        $lower = mb_strtolower($value);
        $is_palindrome = $lower === strrev($lower);
        $unique_chars = count(array_unique(preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY)));
        $word_count = str_word_count($value);
        $sha256 = hash('sha256', $value);

        $freq_map = [];
        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            $freq_map[$char] = ($freq_map[$char] ?? 0) + 1;
        }

        return [
            "length" => $length,
            "is_palindrome" => $is_palindrome,
            "unique_characters" => $unique_chars,
            "word_count" => $word_count,
            "sha256_hash" => $sha256,
            "character_frequency_map" => $freq_map
        ];
    }
}
