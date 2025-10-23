<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Capsule\Manager as DB;

class ApiUnitTest extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up an in-memory SQLite database for tests
        $capsule = new DB;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Create the 'strings' table for testing
        DB::schema()->create('strings', function ($table) {
            $table->string('id')->primary();
            $table->string('value');
            $table->json('properties');
            $table->timestamps();
        });

        $this->client = new Client([
            'base_uri' => 'http://localhost:8000',
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    protected function tearDown(): void
    {
        DB::schema()->dropIfExists('strings'); // Drop the table to clean up after each test
        DB::disconnect(); // Disconnect the in-memory database
        parent::tearDown();
    }

    /**
     * Helper to post a string and handle potential 409 conflicts.
     * Returns decoded response data on success (201).
     */
    private function postString(string $value): array
    {
        try {
            $res = $this->client->post('/strings', ['json' => ['value' => $value]]);
            return json_decode($res->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 409) {
                // If already exists, try to fetch it to ensure it's there
                $res = $this->client->get('/strings/' . $value);
                return json_decode($res->getBody()->getContents(), true);
            }
            throw $e;
        }
    }

    /**
     * Helper to assert API response structure and status code, handling ClientException for errors.
     * Returns decoded response data.
     */
    private function assertApiResponse(
        callable $apiCall,
        int $expectedStatusCode,
        ?string $expectedErrorMessage = null,
        array $expectedKeys = []
    ): array
    {
        $data = [];
        $responseBody = '';

        try {
            $response = $apiCall();
            $this->assertEquals($expectedStatusCode, $response->getStatusCode());
            $responseBody = $response->getBody()->getContents();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->assertEquals($expectedStatusCode, $response->getStatusCode());
            $responseBody = $response->getBody()->getContents();
            if ($expectedErrorMessage !== null) {
                $decodedError = json_decode($responseBody, true);
                $this->assertArrayHasKey('error', $decodedError);
                $this->assertEquals($expectedErrorMessage, $decodedError['error']);
                return $decodedError;
            }
        }

        // Only attempt to decode JSON if there's a body and it's not a 204 No Content
        if ($expectedStatusCode !== 204 && !empty($responseBody)) {
            $data = json_decode($responseBody, true);
            // Ensure JSON decoding was successful before proceeding with key assertions
            $this->assertIsArray($data, "Response body is not valid JSON or is empty: " . $responseBody);
        }

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        return $data;
    }

    public function test_create_string_success() {
        $value = 'hello_' . uniqid(); // Use a unique string for each test run
        $data = $this->assertApiResponse(
            fn() => $this->client->post('/strings', ['json' => ['value' => $value]]),
            201,
            null,
            ['id', 'value', 'properties', 'created_at']
        );
        $this->assertEquals($value, $data['value']);
    }

    public function test_create_string_duplicate() {
        $value = 'test_duplicate';
        $this->postString($value); // Ensure it exists

        $this->assertApiResponse(
            fn() => $this->client->post('/strings', ['json' => ['value' => $value]]),
            409,
            'String already exists'
        );
    }

    public function test_create_string_missing_value() {
        $this->assertApiResponse(
            fn() => $this->client->post('/strings', ['json' => []]),
            400,
            'Missing \'value\' field'
        );
    }

    public function test_create_string_invalid_value_type() {
        $this->assertApiResponse(
            fn() => $this->client->post('/strings', ['json' => ['value' => 123]]),
            422,
            '\'value\' must be a string'
        );
    }

    public function test_get_string_success() {
        $value = 'get_test_string';
        $this->postString($value);

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings/' . $value),
            200,
            null,
            ['id', 'value']
        );
        $this->assertEquals($value, $data['value']);
    }

    public function test_get_string_not_found() {
        $this->assertApiResponse(
            fn() => $this->client->get('/strings/non_existent_string'),
            404,
            'String does not exist in the system'
        );
    }

    public function test_filter_strings_by_palindrome() {
        $this->postString('madam');
        $this->postString('level');
        $this->postString('hello');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings?is_palindrome=true'),
            200,
            null,
            ['data', 'count']
        );
        $this->assertGreaterThanOrEqual(2, count($data['data']));
        foreach ($data['data'] as $string) {
            $this->assertTrue($string['properties']['is_palindrome']);
        }
    }

    public function test_filter_strings_by_min_length() {
        $this->postString('short');
        $this->postString('longerstring');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings?min_length=10'),
            200,
            null,
            ['data', 'count']
        );
        foreach ($data['data'] as $string) {
            $this->assertGreaterThanOrEqual(10, $string['properties']['length']);
        }
    }

    public function test_filter_strings_by_max_length() {
        $this->postString('short');
        $this->postString('verylongstring');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings?max_length=8'),
            200,
            null,
            ['data', 'count']
        );
        foreach ($data['data'] as $string) {
            $this->assertLessThanOrEqual(8, $string['properties']['length']);
        }
    }

    public function test_filter_strings_by_word_count() {
        $this->postString('singleword');
        $this->postString('two words');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings?word_count=2'),
            200,
            null,
            ['data', 'count']
        );
        foreach ($data['data'] as $string) {
            $this->assertEquals(2, $string['properties']['word_count']);
        }
    }

    public function test_filter_strings_by_character() {
        $this->postString('apple');
        $this->postString('banana');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings?contains_character=b'),
            200,
            null,
            ['data', 'count']
        );
        $this->assertGreaterThanOrEqual(1, count($data['data']));
        foreach ($data['data'] as $string) {
            $this->assertStringContainsString('b', $string['value']);
        }
    }

    public function test_filter_strings_invalid_param_type_error() {
        $this->assertApiResponse(
            fn() => $this->client->get('/strings?min_length=abc'),
            400,
            'Invalid type for \'min_length\'. Must be an integer.'
        );

        $this->assertApiResponse(
            fn() => $this->client->get('/strings?is_palindrome=invalid'),
            400,
            'Invalid type for \'is_palindrome\'. Must be \'true\' or \'false\'.'
        );

        $this->assertApiResponse(
            fn() => $this->client->get('/strings?contains_character=ab'),
            400,
            'Invalid value for \'contains_character\'. Must be a single character.'
        );
    }

    public function test_filter_strings_by_natural_language_palindrome() {
        $this->postString('madam');
        $this->postString('racecar');
        $this->postString('apple');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=all%20palindromic%20strings'),
            200,
            null,
            ['data', 'count', 'interpreted_query']
        );
        $this->assertGreaterThanOrEqual(2, count($data['data']));
        foreach ($data['data'] as $string) {
            $this->assertTrue($string['properties']['is_palindrome']);
        }
    }

    public function test_filter_strings_by_natural_language_missing_query() {
        $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language'),
            400,
            'Missing query param'
        );
    }

    public function test_filter_strings_by_natural_language_no_valid_filters() {
        $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=random%20unparseable%20text'),
            400,
            'Unable to parse natural language query'
        );
    }

    public function test_filter_strings_by_natural_language_conflicting_filters_error() {
        $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=longer%20than%2010%20and%20shorter%20than%205'),
            422,
            'Query parsed but resulted in conflicting filters'
        );
    }

    public function test_filter_strings_by_natural_language_longer_than_x() {
        $this->postString('short');
        $this->postString('longerstring');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=strings%20longer%20than%205%20characters'),
            200,
            null,
            ['data', 'count']
        );
        foreach ($data['data'] as $string) {
            $this->assertGreaterThan(5, $string['properties']['length']);
        }
    }

    public function test_filter_strings_by_natural_language_shorter_than_x() {
        $this->postString('short');
        $this->postString('verylongstring');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=strings%20shorter%20than%2010%20characters'),
            200,
            null,
            ['data', 'count']
        );
        foreach ($data['data'] as $string) {
            $this->assertLessThan(10, $string['properties']['length']);
        }
    }

    public function test_filter_strings_by_natural_language_contains_character() {
        $this->postString('apple');
        $this->postString('banana');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=strings%20containing%20the%20letter%20b'),
            200,
            null,
            ['data', 'count']
        );
        $this->assertGreaterThanOrEqual(1, count($data['data']));
        foreach ($data['data'] as $string) {
            $this->assertStringContainsString('b', $string['value']);
        }
    }

    public function test_filter_strings_by_natural_language_single_word_palindromic() {
        $this->postString('level');
        $this->postString('madam');
        $this->postString('hello world');

        $data = $this->assertApiResponse(
            fn() => $this->client->get('/strings/filter-by-natural-language?query=all%20single%20word%20palindromic%20strings'),
            200,
            null,
            ['data', 'count']
        );
        $this->assertGreaterThanOrEqual(2, count($data['data']));
        foreach ($data['data'] as $string) {
            $this->assertTrue($string['properties']['is_palindrome']);
            $this->assertEquals(1, $string['properties']['word_count']);
        }
    }

    public function test_delete_string_success() {
        $value = 'delete_me';
        $this->postString($value);

        $this->assertApiResponse(
            fn() => $this->client->delete('/strings/' . $value),
            204
        );

        $this->assertApiResponse(
            fn() => $this->client->get('/strings/' . $value),
            404,
            'String does not exist in the system'
        );
    }

    public function test_delete_string_not_found() {
        $this->assertApiResponse(
            fn() => $this->client->delete('/strings/non_existent_delete'),
            404,
            'String does not exist in the system'
        );
    }
}
