<?php
/**
 * Created by PhpStorm.
 * User: Mig
 * Date: 2/16/18
 * Time: 21:12
 */

namespace App\Traits;

trait CrawlerTrait{

    public function sendGetRequest($url){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36'
        ));
        $response = curl_exec($curl);
        #$data = json_decode($response, true);
        curl_close($curl);
        return $response;
    }
    
}