<?php
////////////////////////////////////////////////////////////////////////////////
//
//  CANTO INTEGRATION PLATFORM
//  Copyright 2011 Canto GmbH
//  All Rights Reserved.
//
//	NOTICE: Canto GmbH permits you to use, modify, and distribute this file
//  in accordance with the terms of the license agreement accompanying it.
//
////////////////////////////////////////////////////////////////////////////////

/**
 * CIPHttpRequest
 * The extension php_curl must be enabled in php.ini
 */
class CIPHttpRequest {

    private $url;
    private $ch;
    private $httpCode;
    private $result;
    private $CONTENT_TYPE_JSON = "application/json";
    private $CONTENT_TYPE_OCTET_STREAM = "application/octet-stream";
    private $USER_AGENT = "PHP CIP.lib";

    /**
     * Create a new instance 
     * @param string $url The base URL to the CIP server
     */
    public function CIPHttpRequest($url) {
        $this->url = $url;
    }

    /**
     * Send the HTTP request with the POST method
     * @param string $data The post parameters
     * @return array The server response as json 
     */
    public function exec($data) {
        $this->ch = curl_init($this->url);

        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->USER_AGENT);

        $this->result = curl_exec($this->ch);
        $this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        //echo "result ".$this->result."  ".$this->url;
        curl_close($this->ch);

        return $this->result;
    }
    
    /**
     * Send JSON to the server
     * @param array $data The json body
     * @return array The server response 
     */
    public function sendJson($data) {
        return $this->sendRequestBody($data, $this->CONTENT_TYPE_JSON);
    }
    
    /**
     * Send request body to the server
     * @param array $data The data to send
     * @param string $contentType The content type of the $data
     * @return array The server response 
     */
    public function sendRequestBody($data, $contentType) {
        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: $contentType"));
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->USER_AGENT);
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
       
        $this->result = curl_exec($this->ch);
        $this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        //echo "result ".$this->result."  ".$this->url;
        curl_close($this->ch);

        return $this->result;
    }

    /**
     * Gets the HTTP code from the last operation
     * @return int The HTTP code 
     */
    public function getHttpCode() {
        return $this->httpCode;
    }
    
    /**
     * Upload a file to the CIP server
     * @param string $filePath The path of the file to upload 
     * @param array $fields The metadata for the uploaded file
     * @return type 
     */
    public function uploadFile ($filePath, $fields)
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->USER_AGENT);
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        $post = array(
            "file" => $filePath,
            "fields" => json_encode($fields),
        );

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        $this->result = curl_exec($this->ch);
        $this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        
        curl_close($this->ch);
        
        return $this->result;
    }
}
?>
