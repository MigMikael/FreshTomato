<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\SenderTrait;
use App\Movie;

class BotController extends Controller
{
    use SenderTrait;
    public function handleMessage(Request $request)
    {
        $events = $request->all();

        if(!is_null($events['events'])){
            foreach ($events['events'] as $event){
                if($event['type'] == 'message' && $event['message']['type'] == 'text'){
                    $text = $event['message']['text'];
                    $replyToken = $event['replyToken'];

                    $text = str_replace("'", "*", $text);
                    if(Movie::where('name', $text)->exists()){
                        $movie = Movie::where('name', $text)->first();
                        $messages1 = [
                            'type' => 'text',
                            'text' => $movie->name
                        ];

                        $messages2 = [
                            'type' => 'text',
                            'text' => 'Directed by '.$movie->director
                        ];

                        $messages3 = [
                            'type' => 'text',
                            'text' => 'Critics score : '.$movie->critics_score . '\n' . 'Audience score : '.$movie->audience_score
                        ];

                        $columns = [
                            'imageUrl' => $movie->poster,
                            'action' => [
                                'type' => 'uri',
                                'label' => 'View Detail',
                                'uri' => $movie->url
                            ]
                        ];

                        $messages4 = [
                            'type' => 'template',
                            'altText' => $movie->name . ' image',
                            'template' => [
                                'type' => 'image_carousel',
                                'columns' => [
                                    $columns
                                ]    
                            ]
                        ];

                        $data = [
                            'replyToken' => $replyToken,
                            'messages' => [
                                $messages1,
                                $messages2,
                                $messages3,
                                $messages4
                            ],
                        ];
                    }else{
                        $messages1 = [
                            'type' => 'text',
                            'text' => 'Not Found'
                        ];

                        $data = [
                            'replyToken' => $replyToken,
                            'messages' => [
                                $messages1,
                            ],
                        ];
                    }

                    $url = 'https://api.line.me/v2/bot/message/reply';
                    $post = json_encode($data);

                    $this->sendPostRequest($url, $post);
                }
            }
        }
    }
}
