<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Movie;

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
        $this->getMovie();

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

    public function check_exist($movie_name){
        $movie = Movie::where('name', $movie_name)->first();

        if (sizeof($movie) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getMovie(){

        $dom = new \DOMDocument();
        $url = 'https://www.rottentomatoes.com/top/bestofrt/';

        $curl = curl_init();

        $html = $this->sendGetRequest($url, $curl);
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
                if ($current_link == '/m/1000626-all_about_eve'){
                    $current_link .= '?';
                }
                $curr_movie_url = 'https://www.rottentomatoes.com'.$current_link;
                #echo $curr_movie_url . '\n';

                $html = $this->sendGetRequest($curr_movie_url, $curl);
                $movie = [];
                $each_movie_dom = new \DOMDocument();
                @$each_movie_dom->loadHTML($html);
                $each_movie_dom->preserveWhiteSpace = false;
                $movie_title = $each_movie_dom->getElementById('movie-title');
                if($movie_title == null){
                    continue;
                }
                $name = $movie_title->textContent;
                $name = trim(preg_replace('/\s+/', ' ', $name));
                $name = str_replace("'", '*', $name);
                if($this->check_exist($name) == true){
                    continue;
                }
                $movie['name'] = $name;

                $year = $movie_title->childNodes->item(1)->textContent;
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
                $movie = Movie::create($movie);
                echo $movie->name . " created successfully\n";
                $count++;
            }
        }
        # echo $count;
        curl_close($curl);
    }
}
