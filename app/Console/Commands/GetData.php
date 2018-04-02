<?php

namespace App\Console\Commands;

use App\Celeb;
use Illuminate\Console\Command;
use App\Movie;
use Illuminate\Support\Facades\Log;

class GetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get data for movie bot';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //$this->control();
        $this->getMovie('https://www.rottentomatoes.com/top/bestofrt/');
    }
    public function control()
    {
        $url = 'https://www.rottentomatoes.com/top/bestofrt/';
        $this->getMovie($url);
        $start = 2001;
        for ($year = $start; $year <= 2018; $year++){
            $complete_url = $url . '?year=' . $year;
            $this->getMovie($complete_url);
            echo '############### '. $complete_url . " ###############\n";
        }
    }

    public function sendGetRequest($url, $curl){
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36'
        ));
        $response = curl_exec($curl);
        return $response;
    }

    public function get_inner_html( $node ){
        $innerHTML= '';
        $children = $node->childNodes;

        foreach ($children as $child){
            $innerHTML .= $child->ownerDocument->saveXML( $child );
        }

        return $innerHTML;
    }

    public function check_movie_exist($movie_name){
        if (Movie::where('name', $movie_name)->exists()) {
            return true;
        } else {
            return false;
        }
    }

    public function check_celeb_exist($celeb_name){
        if(Celeb::where('name', $celeb_name)->exists()){
            return true;
        } else {
            return false;
        }
    }

    public function getMovie($url){

        $dom = new \DOMDocument();

        $curl = curl_init();

        $html = $this->sendGetRequest($url, $curl);
        @$dom->loadHTML($html);

        $main_container = $dom->getElementById('main_container');
        $movie_table = $main_container->getElementsByTagName('table');
        $table_html = $this->get_inner_html($movie_table->item(0));

        @$dom->loadHTML($table_html);
        $links = $dom->getElementsByTagName('a');

        $count = 1;
        foreach ($links as $link){
            $current_link = $link->getAttribute('href');
            if (substr($current_link,0,3) == '/m/'){
                if ($current_link == '/m/1000626-all_about_eve'){
                    $current_link .= '?';
                }
                $curr_movie_url = 'https://www.rottentomatoes.com'.$current_link;

                $html = $this->sendGetRequest($curr_movie_url, $curl);
                $movie = [];
                $each_movie_dom = new \DOMDocument();
                @$each_movie_dom->loadHTML($html);
                $each_movie_dom->preserveWhiteSpace = false;

                $movie_title = $each_movie_dom->getElementById('movie-title');
                if($movie_title == null){
                    continue;
                }

                $year = $movie_title->childNodes->item(1)->textContent;

                $name = $movie_title->textContent;
                $name = trim(preg_replace('/\s+/', ' ', $name));
                $name = str_replace($year, '', $name);
                $name = str_replace("'", '*', $name);
                if($this->check_movie_exist($name) == true){
                    continue;
                }

                echo "-------------------------------------\n";
                echo "[ ".$count." ]\n";
                echo "Begin ". $current_link . "\n";

                $movie['name'] = $name;

                $year = str_replace('(', '', $year);
                $year = str_replace(')', '', $year);
                $movie['year'] = (int)$year;

                $critic = $each_movie_dom->getElementById('all-critics-numbers');
                $critic_score = $critic->getElementsByTagName('span')->item(1)->textContent;
                $movie['critics_score'] = $critic_score;

                $fresh = $critic->getElementsByTagName('span')->item(7)->textContent;
                $rotten = $critic->getElementsByTagName('span')->item(9)->textContent;
                $movie['fresh_rotten'] = $fresh . ':' . $rotten;

                $image_section = $each_movie_dom->getElementById('movie-image-section');
                #$image_section = $each_movie_dom->getElementById('photos-carousel-root');
                $img = $image_section->getElementsByTagName('img');
                $poster_url = $img->item(0)->getAttribute('src');
                $movie['poster'] = $poster_url;

                $all_div = $each_movie_dom->getElementsByTagName('div');
                $all_div_count = $each_movie_dom->getElementsByTagName('div')->length;
                for ($i = 0; $i < $all_div_count; $i++){
                    if ($all_div->item($i)->getAttribute('class') == 'audience-score meter'){
                        $audience_score =  $all_div->item($i)->textContent;
                        $audience_score = str_replace('liked it', '', $audience_score);
                        $audience_score = str_replace('\n', '', $audience_score);
                        $audience_score = trim(preg_replace('/\s+/', ' ', $audience_score));
                        $movie['audience_score'] = $audience_score;
                    }
                }

                $info = $each_movie_dom->getElementById('movieSynopsis');
                $info = $info->textContent;
                $info = str_replace(',', '|', $info);
                $info = trim(preg_replace('/\s+/', ' ', $info));
                $info = str_replace("'", '*', $info);
                $movie['info'] = $info;

                $mv_main_container = $each_movie_dom->getElementById('main_container');
                $movie_ul = $mv_main_container->getElementsByTagName('ul');
                $ul_html = $this->get_inner_html($movie_ul->item(4));


                $dom2 = new \DOMDocument();
                @$dom2->loadHTML($ul_html);
                $div = $dom2->getElementsByTagName('div');
                $div_count = $dom2->getElementsByTagName('div')->length;

                for ($i = 0; $i < $div_count; $i+=2){
                    if ($div->item($i)->textContent == 'Rating: '){
                        $movie['rating'] = $div->item($i + 1)->textContent;
                    }elseif ($div->item($i)->textContent == 'Genre: '){
                        $genre = $div->item($i + 1)->textContent;
                        $genre = str_replace(',', '|', $genre);
                        $genre = str_replace('\n', '', $genre);
                        $genre = trim(preg_replace('/\s+/', ' ', $genre));
                        $movie['genre'] = $genre;
                    }elseif ($div->item($i)->textContent == 'Directed By: '){
                        $director = $div->item($i + 1)->textContent;
                        $director = str_replace(',', '|', $director);
                        $director = trim(preg_replace('/\s+/', ' ', $director));
                        $director = str_replace("'", '*', $director);
                        $movie['director'] = $director;
                    }elseif ($div->item($i)->textContent == 'Written By: '){
                        $writer = $div->item($i + 1)->textContent;
                        $writer = str_replace(',', '|', $writer);
                        $writer = trim(preg_replace('/\s+/', ' ', $writer));
                        $writer = str_replace("'", '*', $writer);
                        $movie['writer'] = $writer;
                    }elseif ($div->item($i)->textContent == 'Runtime: '){
                        $runTime = $div->item($i + 1)->textContent;
                        $runTime = str_replace(' minutes', '', $runTime);
                        $movie['runtime'] = (int) $runTime;
                    }
                }
                $movie['url'] = $curr_movie_url;
                $movie = Movie::create($movie);
                echo "Finish ". $current_link . "\n";
                $count++;

                $all_a_tags = $each_movie_dom->getElementsByTagName('a');
                $celeb_count = 0;
                foreach ($all_a_tags as $tag){
                    $l = $tag->getAttribute('href');
                    if(strpos($l, '/celebrity/') !== false){
                        try{
                            $celeb_link = 'https://www.rottentomatoes.com'.$l;
                            $celeb_html = $this->sendGetRequest($celeb_link, $curl);
                            $each_celeb_dom = new \DOMDocument();
                            @$each_celeb_dom->loadHTML($celeb_html);
                            $each_celeb_dom->preserveWhiteSpace = false;

                            $celeb = [];
                            $celeb_name = $each_celeb_dom->getElementsByTagName('h1');
                            if($celeb_name == ''){
                                continue;
                            }

                            $celeb['name'] = $celeb_name->item(0)->textContent;
                            if ($this->check_celeb_exist($celeb['name'])){
                                continue;
                            }
                            if ($celeb['name'] == '404 - Not Found'){
                                continue;
                            }

                            $time_tags = $each_celeb_dom->getElementsByTagName('time');
                            if($time_tags->item(0) != ''){
                                $celeb['birthday'] = $time_tags->item(0)->textContent;
                            }else{
                                $celeb['birthday'] = 'NotAvailable';
                            }

                            $birthplace = $each_celeb_dom->getElementsByTagName('div')->item(140)->textContent;
                            $birthplace = str_replace('Birthplace:', '',$birthplace);
                            $birthplace = preg_replace('/\s+/', '', $birthplace);
                            $birthplace = str_replace(' ', '', $birthplace);
                            $celeb['birthplace'] = $birthplace;
                            $celeb['info'] = $each_celeb_dom->getElementsByTagName('div')->item(141)->textContent;
                            $image = $each_celeb_dom->getElementsByTagName('div')->item(135)->getAttribute('style');
                            $image = str_replace("background-image:url('", '', $image);
                            $celeb['image'] = substr($image, 0, -2);

                            $celeb['highest_rate'] = $each_celeb_dom->getElementsByTagName('span')->item('72')->textContent;
                            $celeb['lowest_rate'] = $each_celeb_dom->getElementsByTagName('span')->item('77')->textContent;
                            $celeb['url'] = $celeb_link;
                            $celeb = Celeb::create($celeb);
                            $celeb_count++;
                        }catch (\ErrorException $e){
                            continue;
                        }
                    }
                }
                echo "(".$celeb_count . " celeb created)\n";
            }
        }
        echo "-------------------------------------\n";
        echo $count . " movies has been created\n";
        curl_close($curl);
    }
}
