<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\SenderTrait;
use App\Movie;
use App\Library\THSplitLib\Segment;
use Illuminate\Support\Facades\Log;

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
                    $this->classifyIntent($text);

                    if(Movie::where('name', $text)->exists()){
                        $movie = Movie::where('name', $text)->first();
                        $messages1 = [
                            'type' => 'text',
                            'text' => $movie->name . ' ('. $movie->year . ')'
                        ];

                        $f_r = explode(':', $movie->fresh_rotten);
                        $messages2 = [
                            'type' => 'text',
                            'text' => 'Fresh : ' . $f_r[0] . ' | Rotten : ' .$f_r[1]
                        ];

                        $messages3 = [
                            'type' => 'text',
                            'text' => 'Critics score : '.$movie->critics_score . ' | Audience score : '.$movie->audience_score
                        ];

                        $columns1 = [
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
                                    $columns1
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

    public function testHandleMessage($text)
    {
        $segment = new Segment();
        $segment_text = $segment->get_segment_array($text);

        $this->classifyIntent($segment_text);

        return $segment_text;
    }

    public function read_word_bag($path)
    {
        $word_arr = [];
        $myfile = fopen($path, "r") or die("Unable to open file!");
        while(!feof($myfile)) {
            array_push($word_arr, fgets($myfile));
        }
        fclose($myfile);

        return $word_arr;
    }

    /**
     * @param $segment_text
     * @return $intent
     *
     * intent consist of 4 types
     * 1. Greeting
     * 2. General
     * 3. Suggest
     * 4. Others
     */
    public function classifyIntent($segment_text)
    {
        $greeting_path = public_path('greeting.txt');
        $greeting_word = $this->read_word_bag($greeting_path);

        foreach ($segment_text as $word){
            Log::info($word);
        }

        $intent = "";

        return $intent;
    }

    public function findAnswer($segment_text, $intent)
    {

    }

    public function chat()
    {
        return view('chat');
    }

    public function ask(Request $request)
    {
        $question = $request->get('question');

        $segment = new Segment();
        $segment_text = $segment->get_segment_array($question);

        $intent = $this->classifyIntent($segment_text);
        $answer = $this->findAnswer($segment_text, $intent);

        $answer = "งง ไปหมดเลยอ่า";

        return view('chat', [
            'question' => $question,
            'answer' => $answer
        ]);
    }
}
