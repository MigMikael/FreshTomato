<?php
/**
 * Created by PhpStorm.
 * User: Mig
 * Date: 2/16/18
 * Time: 21:12
 */

namespace App\Traits;

trait SenderTrait{

    public function sendGetRequest($url)
    {
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

    public function sendPostRequest($url, $data)
    {
        $access_token = env('LINEBOT_ACCESS_TOKEN','');
        $header = array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_FOLLOWLOCATION => 1,
        ));
        $response = curl_exec($curl);
        //$data = json_decode($response, true);
        curl_close($curl);
    }
}