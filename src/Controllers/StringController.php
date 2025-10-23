<?php
namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Carbon\Carbon;
use App\StringService;

class StringController {
    public static function create(Request $request, Response $response) {
        $body = $request->getParsedBody();

        if (!isset($body['value'])) {
            return self::json($response, ["error" => "Missing 'value' field"], 400);
        }

        if (!is_string($body['value'])) {
            return self::json($response, ["error" => "'value' must be a string"], 422);
        }

        $value = $body['value'];
        $hash = hash('sha256', $value);

        if (DB::table('strings')->where('id', $hash)->exists()) {
            return self::json($response, ["error" => "String already exists"], 409);
        }

        $props = StringService::analyze($value);

        DB::table('strings')->insert([
            'id' => $hash,
            'value' => $value,
            'properties' => json_encode($props),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return self::json($response, [
            "id" => $hash,
            "value" => $value,
            "properties" => $props,
            "created_at" => gmdate("Y-m-d\TH:i:s\Z")
        ], 201);
    }

    public static function getByValue(Request $request, Response $response, $args) {
        $value = $args['string_value'];
        $hash = hash('sha256', $value);
    
        $record = DB::table('strings')->where('id', $hash)->first();
    
        if (!$record) {
            return self::json($response, ["error" => "String does not exist in the system"], 404);
        }
    
        return self::json($response, [
            "id" => $record->id,
            "value" => $record->value,
            "properties" => json_decode($record->properties, true),
            "created_at" => $record->created_at,
            "updated_at" => $record->updated_at
        ]);
    }

	public static function delete(Request $request, Response $response, $args) {
		$value = $args['string_value'];
		$hash = hash('sha256', $value);
	
		$deleted = DB::table('strings')->where('id', $hash)->delete();
	
		if (!$deleted) {
			return self::json($response, ["error" => "String does not exist in the system"], 404);
		}
	
		return $response->withStatus(204);
	}

    public static function json(Response $res, $data, $code = 200) {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($code);
    }
}
