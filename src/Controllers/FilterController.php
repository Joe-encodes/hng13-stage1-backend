<?php
namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\StringService;

class FilterController {
    public static function filter(Request $request, Response $response) {
        $params = $request->getQueryParams();

        // Explicit validation for query parameters
        if (isset($params['is_palindrome']) && !in_array(strtolower($params['is_palindrome']), ['true', 'false'])) {
            return StringController::json($response, ["error" => "Invalid type for 'is_palindrome'. Must be 'true' or 'false'."], 400);
        }
        if (isset($params['min_length']) && !ctype_digit($params['min_length'])) {
            return StringController::json($response, ["error" => "Invalid type for 'min_length'. Must be an integer."], 400);
        }
        if (isset($params['max_length']) && !ctype_digit($params['max_length'])) {
            return StringController::json($response, ["error" => "Invalid type for 'max_length'. Must be an integer."], 400);
        }
        if (isset($params['word_count']) && !ctype_digit($params['word_count'])) {
            return StringController::json($response, ["error" => "Invalid type for 'word_count'. Must be an integer."], 400);
        }
        if (isset($params['contains_character']) && (empty($params['contains_character']) || mb_strlen($params['contains_character'], 'UTF-8') !== 1)) {
            return StringController::json($response, ["error" => "Invalid value for 'contains_character'. Must be a single character."], 400);
        }

        $filteredData = StringService::filterStrings($params);

        $responseData = [
            "data" => $filteredData['data'] ?? [],
            "count" => $filteredData['count'] ?? 0,
            "filters_applied" => $params
        ];
        return StringController::json($response, $responseData);
    }

    public static function filterByNatural(Request $request, Response $response) {
        $params = $request->getQueryParams();
        if (!isset($params['query']))
            return StringController::json($response, ["error" => "Missing query param"], 400);

        $query = strtolower($params['query']);
        $filters = [];

        if (str_contains($query, 'palindromic')) $filters['is_palindrome'] = true;
        if (preg_match('/longer than (\d+)/', $query, $m)) $filters['min_length'] = (int)$m[1] + 1;
        if (preg_match('/shorter than (\d+)/', $query, $m)) $filters['max_length'] = (int)$m[1] - 1;
        if (preg_match('/containing the letter (\w)/', $query, $m)) $filters['contains_character'] = $m[1];
        if (str_contains($query, 'single word')) $filters['word_count'] = 1;

        // Check for conflicting filters
        if (isset($filters['min_length']) && isset($filters['max_length']) && $filters['min_length'] > $filters['max_length']) {
            return StringController::json($response, ["error" => "Query parsed but resulted in conflicting filters"], 422);
        }

        if (empty($filters))
            return StringController::json($response, ["error" => "Unable to parse natural language query"], 400);

        $filteredData = StringService::filterStrings($filters);

        $responseData = [
            "data" => $filteredData['data'] ?? [],
            "count" => $filteredData['count'] ?? 0,
            "interpreted_query" => [
                "original" => $params['query'],
                "parsed_filters" => $filters
            ]
        ];
        return StringController::json($response, $responseData);
    }
}
