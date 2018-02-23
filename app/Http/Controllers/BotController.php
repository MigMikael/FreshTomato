<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\SenderTrait;

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

                    $messages1 = [
                        'type' => 'text',
                        'text' => $text
                    ];

                    $data = [
                        'replyToken' => $replyToken,
                        'messages' => [
                            $messages1
                        ],
                    ];

                    $url = 'https://api.line.me/v2/bot/message/reply';
                    $post = json_encode($data);

                    $this->sendPostRequest($url, $post);
                }
            }
        }
    }
}
