<?php
namespace App;

use Illuminate\Database\Capsule\Manager as DBManager;

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

    public static function filterStrings($params) {
        $results = DBManager::table('strings')->get()->map(function ($item) {
            $item->properties = json_decode($item->properties, true);
            return $item;
        })->toArray();

        $filtered = array_filter($results, function ($row) use ($params) {
            $props = $row->properties;
            if (isset($params['is_palindrome']) && (bool)$props['is_palindrome'] !== filter_var($params['is_palindrome'], FILTER_VALIDATE_BOOLEAN)) return false;
            if (isset($params['min_length']) && $props['length'] < intval($params['min_length'])) return false;
            if (isset($params['max_length']) && $props['length'] > intval($params['max_length'])) return false;
            if (isset($params['contains_character']) && !str_contains($row->value, $params['contains_character'])) return false;
            if (isset($params['word_count']) && $props['word_count'] != intval($params['word_count'])) return false;
            return true;
        });

        return [
            "data" => array_values($filtered),
            "count" => count($filtered),
        ];
    }
}
