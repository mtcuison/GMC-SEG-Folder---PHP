<?php
  
class WebClient{
    public static function httpsPostJson($url, $data, $headers){
        $curl = curl_init($url);
        
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        // Submit the POST request
        $result = curl_exec($curl);
        
	//Handle errors that doesn't return any $result...
	if ($result  === false){
	   $result  = stripslashes(curl_error($curl));
	}
        // Close cURL session handle
        curl_close($curl);
        
        return $result;
    }
    
    public static function httpRequest($method, $url, $data, $headers){
        $curl = curl_init();
        switch ($method){
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        //options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        //execute
        $result = curl_exec($curl);
        
        //Handle errors that doesn't return any $result...
        if ($result  === false){
            $result  = stripslashes(curl_error($curl));
        }
        
        // Close cURL session handle
        curl_close($curl);
        
        return $result;
    }
    
    public static function httpsGetJson($url, $data, $headers){
        $curl = curl_init($url);
        
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        //curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        
        $xdata = http_build_query($data);
        //curl_setopt($curl, CURLOPT_POSTFIELDS,$xdata);
        //echo str_replace("%40", "@", $xdata);
        curl_setopt($curl, CURLOPT_POSTFIELDS,str_replace("%40", "@", $xdata));
        
        //echo str_replace("%40", "@", http_build_query($data));
        
        // Submit the PUT request
        $result = curl_exec($curl);
        
        //Handle errors that doesn't return any $result...
        if ($result === false){
            $result  = stripslashes(curl_error($curl));
        }
        
        // Close cURL session handle
        curl_close($curl);
        
        return $result;
    }
}
?>