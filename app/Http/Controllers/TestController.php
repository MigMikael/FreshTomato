<?php

namespace App\Http\Controllers;
use App\Traits\SenderTrait;
use Illuminate\Http\Request;
use App\Movie;
use App\Library\THSplitLib\Segment;

class TestController extends Controller
{
    use SenderTrait;

    function get_inner_html( $node ){
        $innerHTML= '';
        $children = $node->childNodes;

        foreach ($children as $child){
            $innerHTML .= $child->ownerDocument->saveXML( $child );
        }

        return $innerHTML;
    }

    public function getMovie(){
        $dom = new \DOMDocument();
        $url = 'https://www.rottentomatoes.com/top/bestofrt/';

        $html = $this->sendGetRequest($url);
        @$dom->loadHTML($html);

        $main_container = $dom->getElementById('main_container');
        $movie_table = $main_container->getElementsByTagName('table');
        $table_html = $this->get_inner_html($movie_table->item(0));

        @$dom->loadHTML($table_html);
        $links = $dom->getElementsByTagName('a');

        $count = 0;
        foreach ($links as $link){
            $current_link = $link->getAttribute('href');
            if (substr($current_link,0,3) == '/m/'){
                $curr_movie_url = 'https://www.rottentomatoes.com'.$current_link;
                $html = $this->sendGetRequest($curr_movie_url);

                $movie = new Movie();
                @$dom->loadHTML($html);
                $dom->preserveWhiteSpace = false;
                $movie_title = $dom->getElementById('movie-title');
                $movie->name = trim(preg_replace('/\s+/', ' ', $movie_title->textContent));

                $year = $movie_title->childNodes->item(1)->textContent;
                $year = str_replace('(', '', $year);
                $year = str_replace(')', '', $year);
                $movie->year = (int)$year;

                $critic = $dom->getElementById('all-critics-numbers');
                $critic_score = $critic->getElementsByTagName('span')->item(1)->textContent;
                $movie->critics_score = $critic_score;

                $fresh = $critic->getElementsByTagName('span')->item(7)->textContent;
                $rotten = $critic->getElementsByTagName('span')->item(9)->textContent;
                $movie->fresh_rotten = $fresh . ':' . $rotten;

                $image_section = $dom->getElementById('movie-image-section');
                $img = $image_section->getElementsByTagName('img');
                $poster_url = $img->item(0)->getAttribute('src');
                $movie->poster = $poster_url;

                $all_div = $dom->getElementsByTagName('div');
                $all_div_count = $dom->getElementsByTagName('div')->length;
                for ($i = 0; $i < $all_div_count; $i++){
                    if ($all_div->item($i)->getAttribute('class') == 'audience-score meter'){
                        $audience_score =  $all_div->item($i)->textContent;
                        $audience_score = str_replace('liked it', '', $audience_score);
                        $audience_score = str_replace('\n', '', $audience_score);
                        $audience_score = trim(preg_replace('/\s+/', ' ', $audience_score));
                        $movie->audience_score = $audience_score;
                        break;
                    }
                }

                $info = $dom->getElementById('movieSynopsis');
                $info = $info->textContent;
                $info = str_replace(',', '|', $info);
                $info = trim(preg_replace('/\s+/', ' ', $info));
                $movie->info = $info;

                $mv_main_container = $dom->getElementById('main_container');
                $movie_ul = $mv_main_container->getElementsByTagName('ul');
                $ul_html = $this->get_inner_html($movie_ul->item(4));

                #echo $ul_html;

                @$dom->loadHTML($ul_html);
                $div = $dom->getElementsByTagName('div');
                $div_count = $dom->getElementsByTagName('div')->length;
                #echo $div_count;

                for ($i = 0; $i < $div_count; $i+=2){
                    echo $div->item($i)->textContent . "<br>";
                    if ($div->item($i)->textContent == 'Rating: '){
                        $movie->rating = $div->item($i + 1)->textContent;
                    }elseif ($div->item($i)->textContent == 'Genre: '){
                        $genre = $div->item($i + 1)->textContent;
                        $genre = str_replace(',', '|', $genre);
                        $genre = str_replace('\n', '', $genre);
                        $genre = trim(preg_replace('/\s+/', ' ', $genre));
                        $movie->genre = $genre;
                    }elseif ($div->item($i)->textContent == 'Directed By: '){
                        $director = $div->item($i + 1)->textContent;
                        $director = str_replace(',', '|', $director);
                        $director = trim(preg_replace('/\s+/', ' ', $director));
                        $movie->director = $director;
                    }elseif ($div->item($i)->textContent == 'Written By: '){
                        $writer = $div->item($i + 1)->textContent;
                        $writer = str_replace(',', '|', $writer);
                        $writer = trim(preg_replace('/\s+/', ' ', $writer));
                        $movie->writer = $writer;
                    }elseif ($div->item($i)->textContent == 'Runtime: '){
                        $runTime = $div->item($i + 1)->textContent;
                        $runTime = str_replace(' minutes', '', $runTime);
                        $movie->runtime = (int) $runTime;
                    }
                }

                print_r($movie);
                $movie->save();
                echo "Created";
                $count++;
                break;
            }
        }
        #echo $count;
    }

    public function test_get_env()
    {
        return getenv("APP_ENV");
    }

    public function test_query()
    {
        $text = 'Toy Story (1995)';
        $movie = Movie::where('name', $text)->first();
        return $movie;
    }

    public function test_split_th()
    {
        $segment = new Segment();
        #$result = $segment->get_segment_array("สวัสดีแนะนำหนังหน่อยครับ");
        #$result = $segment->get_segment_array("ช่วยแนะนำหนังหน่อยนะ");
        #$result = $segment->get_segment_array("ใครเป็นผู้กำกับหนังเรื่อง The Godfather");
        $result = $segment->get_segment_array("ใครเป็นผกก.หนังเรื่อง The Godfather");


        return $result;
    }
}
