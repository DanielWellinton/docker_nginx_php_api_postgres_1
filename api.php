<?php

class API {
    private String $url;
    private CurlHandle|false $ch;
    private String $apiKey;

    public function __construct()
    {
        $this->url = "https://api.thecatapi.com/v1/images/search?limit=10";
        $this->apiKey = getenv("API_KEY");
    }

    public function getApi()
    {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $this->apiKey"
            ]
        ]);

        $response = curl_exec($this->ch);
        
        if (curl_errno($this->ch)) {
            $error = curl_error($this->ch);
            curl_close($this->ch);
            return [
                "error" => "Erro ao executar a requisição cURL: $error"
            ];
        }
    
        $httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            curl_close($this->ch);
            return [
                "error" => "Erro da API: Código de status HTTP $httpCode"
            ];
        }

        $catsData = json_decode($response, true);
        curl_close($this->ch);
        return $catsData;
    }
}
