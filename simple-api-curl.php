<?php

function steedy_api_simple_call($endpoint, array $parameters, $client_id = NULL, $token = NULL){
    $api_version = 'v1'; // or use 'v1-sandbox' for testing 
    $request_body = empty($parameters) ? '' : json_encode($parameters);
    $request_headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Length: ' . mb_strlen($request_body)
    );
    
    if(!empty($client_id)){
        $request_headers[] = 'X-Steedy-ClientID: ' . $client_id;
    }
    
    if(!empty($token)){
        $request_headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $response_headers = array();
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL,            'https://www.1steedy.fr/api/' . $api_version . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST,           TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $request_body);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     $request_headers);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers){
        $len = strlen($header);
        $header = explode(':', $header, 2);
        
        if (count($header) < 2){
            return $len;    
        }

        $name = strtolower(trim($header[0]));
        if (!array_key_exists($name, $response_headers)){
            $response_headers[$name] = [trim($header[1])];    
        }
        else{
            $response_headers[$name][] = trim($header[1]);    
        }

        return $len;
    });

    $reponse = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($reponse);
}
