<?php
/**
 * Created by PhpStorm.
 * User: Mig
 * Date: 2/17/18
 * Time: 8:26
 */

function sendGetRequest($url){
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

function getMovie()
{
    $url = 'https://www.rottentomatoes.com/top/bestofrt/';
    $html = sendGetRequest($url);

    $dom = new \DOMDocument();

    @$dom->loadHTML($html);

    $links = $dom->getElementsByTagName('a');

    $count = 0;
    foreach ($links as $link){
        $current_link = $link->getAttribute('href');
        if (substr($current_link,0,3) == '/m/'){
            $curr_movie_url = 'https://www.rottentomatoes.com'.$current_link;
            echo $curr_movie_url;

            $movie_html = sendGetRequest($curr_movie_url);
            @$dom->loadHTML($movie_html);

            $movie_title = $dom->getElementById('movie-title');
            echo $movie_title->textContent;

            $count++;
        }
    }
    echo $count;
}

getMovie();