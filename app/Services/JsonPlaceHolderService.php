<?php

namespace App\Services;

class JsonPlaceHolderService {

    public $ch;

    public function __construct()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string instead of outputting it directly

    }

    /**
     * Get Users From JsonPlaceHolder Api
     */
    public function getJsonFromJPlaceHolder(string $endpoint) {
        curl_setopt($this->ch, CURLOPT_URL, "https://jsonplaceholder.typicode.com/{$endpoint}");
        $response = curl_exec($this->ch);

        if (curl_errno($this->ch)) {
            throw new \Exception('Curl error:', curl_error($this->ch));
        }
        // Step 4: Close the cURL session
        curl_close($this->ch);
        return json_decode($response, true);

    }

}