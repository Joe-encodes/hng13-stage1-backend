<?php
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

class StringController {
    public static function create($request, $response) {
        $body = json_decode($request->getBody()->getContents(), true);
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

    public static function json($res, $data, $code = 200) {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($code);
    }
}
