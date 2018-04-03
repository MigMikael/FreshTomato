<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Movie;
use App\Celeb;
use App\MovieCast;
use Illuminate\Support\Facades\Log;

class CrawlData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl data from Rotten Tomatoes';

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

        $curl = curl_init();

        /*
        $url = 'https://www.rottentomatoes.com/celebrity/771811333';
        $url = 'https://www.rottentomatoes.com/celebrity/armie-hammer';
        $url = 'https://www.rottentomatoes.com/celebrity/timothee_chalamet_2';
        $this->crawlCeleb($url, $curl);
        */

        $url = 'https://www.rottentomatoes.com/top/bestofrt/';
        $this->crawlMovieList($url, $curl);

        /*
        $start = 1960;
        $end = 2018;
        for ($year = $start; $year <= $end; $year++){
            echo "################################### ". $year . " ###################################\n";
            $complete_url = $url . '?year=' . $year;
            $this->crawlMovieList($complete_url, $curl);
        }
        */

        curl_close($curl);
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

    public function crawlMovieList($url, $curl)
    {
        $dom = new \DOMDocument();

        $html = $this->sendGetRequest($url, $curl);
        @$dom->loadHTML($html);

        $main_container = $dom->getElementById('main_container');
        $movie_table = $main_container->getElementsByTagName('table');
        $table_html = $this->get_inner_html($movie_table->item(0));

        $table_dom = new \DOMDocument();
        @$table_dom->loadHTML($table_html);
        $links = $table_dom->getElementsByTagName('a');

        $count = 1;
        foreach ($links as $link){
            $current_link = $link->getAttribute('href');
            if (substr($current_link, 0, 3) == '/m/'){
                $full_movie_url = 'https://www.rottentomatoes.com'.$current_link;
                try{
                    echo "______________________________________________________________________\n";
                    echo "[ ".$count." ]\n";
                    echo "Begin ". $current_link . "\n";

                    $is_continue = $this->crawlMovie($full_movie_url, $curl);
                    if($is_continue){
                        $skip_text = "Skip 1 ". $current_link;
                        echo  $skip_text . "\n";
                        Log::info($skip_text);
                        continue;
                    }else{
                        echo "Finish\n";
                        $count++;
                    }
                }catch (\Exception $e){
                    $skip_text = "Skip 2 ". $current_link;
                    echo $skip_text . "\n";
                    Log::info($skip_text);
                    continue;
                }
            }
        }
    }

    public function crawlMovie($url, $curl)
    {
        $html = $this->sendGetRequest($url, $curl);
        $movie = [];

        $each_movie_dom = new \DOMDocument();
        @$each_movie_dom->loadHTML($html);
        $each_movie_dom->preserveWhiteSpace = false;

        $movie_title = $each_movie_dom->getElementById('movie-title');
        $year = $movie_title->childNodes->item(1)->textContent;

        $name = $movie_title->textContent;
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $name = str_replace($year, '', $name);
        $name = str_replace("'", '*', $name);
        if($this->check_movie_exist($name) == true){
            return true;
        }

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
                break;
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

        $director_list = [];
        $writer_list = [];
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

                $director_html = $this->get_inner_html($div->item($i + 1));
                $director_dom = new \DOMDocument();
                @$director_dom->loadHTML($director_html);
                $director_count = $director_dom->getElementsByTagName('a')->length;

                for ($j = 0; $j < $director_count; $j++){
                    $director_link = $director_dom->getElementsByTagName('a')
                        ->item($j)->getAttribute('href');
                    array_push($director_list, $director_link);
                }
            }elseif ($div->item($i)->textContent == 'Written By: '){
                $writer = $div->item($i + 1)->textContent;
                $writer = str_replace(',', '|', $writer);
                $writer = trim(preg_replace('/\s+/', ' ', $writer));
                $writer = str_replace("'", '*', $writer);
                $movie['writer'] = $writer;

                $writer_html = $this->get_inner_html($div->item($i + 1));
                $writer_dom = new \DOMDocument();
                @$writer_dom->loadHTML($writer_html);
                $writer_count = $writer_dom->getElementsByTagName('a')->length;

                for ($j = 0; $j < $writer_count; $j++){
                    $writer_link = $writer_dom->getElementsByTagName('a')
                        ->item($j)->getAttribute('href');
                    array_push($writer_list, $writer_link);
                }
            }elseif ($div->item($i)->textContent == 'Runtime: '){
                $runTime = $div->item($i + 1)->textContent;
                $runTime = str_replace(' minutes', '', $runTime);
                $movie['runtime'] = (int) $runTime;
            }
        }

        $movie['url'] = $url;
        $movie = Movie::create($movie);

        foreach ($director_list as $dir){
            try{
                $celeb_link = 'https://www.rottentomatoes.com' . $dir;
                $celeb = $this->crawlCeleb($celeb_link, $curl);
                if ($celeb == 'skip'){
                    continue;
                }else{
                    $movie_cast = [];
                    $movie_cast['movie_id'] = $movie->id;
                    $movie_cast['celeb_id'] = $celeb->id;
                    $movie_cast['relation'] = 'director';
                    MovieCast::firstOrCreate($movie_cast);
                    echo ".";
                }
            }catch (\Exception $e){
                continue;
            }
        }

        foreach ($writer_list as $dir){
            try{
                $celeb_link = 'https://www.rottentomatoes.com' . $dir;
                $celeb = $this->crawlCeleb($celeb_link, $curl);
                if ($celeb == 'skip'){
                    continue;
                }else{
                    $movie_cast = [];
                    $movie_cast['movie_id'] = $movie->id;
                    $movie_cast['celeb_id'] = $celeb->id;
                    $movie_cast['relation'] = 'writer';
                    MovieCast::firstOrCreate($movie_cast);
                    echo ".";
                }
            }catch (\Exception $e){
                continue;
            }
        }

        $castSectionIndex = 0;
        for ($i = 0; $i < $all_div_count; $i++){
            if ($all_div->item($i)->getAttribute('class') == 'castSection '){
                $castSectionIndex = $i;
                break;
            }
        }
        $cast_section_html = $this->get_inner_html($all_div->item($castSectionIndex));

        $cast_section_dom = new \DOMDocument();
        @$cast_section_dom->loadHTML($cast_section_html);
        $aTag = $cast_section_dom->getElementsByTagName('a');
        $celeb_list = [];
        for($i = 0; $i < $aTag->length; $i++){
            $l = $aTag->item($i)->getAttribute('href');
            array_push($celeb_list, $l);
        }
        $celeb_list = array_unique($celeb_list);
        $this->crawlCelebList($celeb_list, $movie, $curl);

        return false;
    }

    public function check_celeb_exist($celeb_name){
        if(Celeb::where('name', $celeb_name)->exists()){
            return true;
        } else {
            return false;
        }
    }

    public function crawlCelebList($celeb_list, $movie, $curl)
    {
        $celeb_count = 0;
        foreach ($celeb_list as $celeb){
            try{
                if(strpos($celeb, '/celebrity/') !== false){
                    $celeb_link = 'https://www.rottentomatoes.com'.$celeb;
                    $celeb = $this->crawlCeleb($celeb_link, $curl);
                    if ($celeb == 'skip'){
                        continue;
                    }else{
                        $movie_cast = [];
                        $movie_cast['movie_id'] = $movie->id;
                        $movie_cast['celeb_id'] = $celeb->id;
                        $movie_cast['relation'] = 'cast';
                        MovieCast::firstOrCreate($movie_cast);
                        $celeb_count++;
                        echo ".";
                    }
                }
            }catch (\Exception $e){
                continue;
            }
        }
        echo "\n(".$celeb_count . " celeb created)\n";
    }

    public function crawlCeleb($url, $curl)
    {
        $celeb_html = $this->sendGetRequest($url, $curl);
        $each_celeb_dom = new \DOMDocument();
        @$each_celeb_dom->loadHTML($celeb_html);
        $each_celeb_dom->preserveWhiteSpace = false;

        $celeb = [];
        $celeb['name'] = $each_celeb_dom->getElementsByTagName('h1')->item(0)->textContent;

        if ($this->check_celeb_exist($celeb['name'])){
            $exist_celeb = Celeb::where('name', $celeb['name'])->first();
            return $exist_celeb;
        }
        if ($celeb['name'] == '404 - Not Found'){
            return 'skip';
        }

        $main_container = $each_celeb_dom->getElementById('main_container');
        $main_container_html = $this->get_inner_html($main_container);
        $main_container_dom = new \DOMDocument();
        @$main_container_dom->loadHTML($main_container_html);

        $divs = $main_container_dom->getElementsByTagName('div');
        $has_bio = false;
        for ($i = 0; $i < $divs->length; $i++){
            $current_div = $divs->item($i);
            if ($current_div->getAttribute('class') == 'celeb_bio_row'){
                $div_text = $current_div->textContent;
                if(strpos($div_text, 'Highest Rated:') !== false){
                    $hi_rate = preg_replace('/[^a-zA-Z0-9,%().]/', '#', $div_text);
                    $hi_rate = str_replace('##', '', $hi_rate);
                    $hi_rate = str_replace('Highest#Rated', '', $hi_rate);
                    $hi_rate = str_replace('#', ' ', $hi_rate);
                    $hi_rate = substr($hi_rate, 1);
                    $celeb['highest_rate'] = $hi_rate;
                    #echo $hi_rate . "\n";

                }elseif(strpos($div_text, 'Lowest Rated:') !== false){
                    $lo_rate = preg_replace('/[^a-zA-Z0-9,%().]/', '#', $div_text);
                    $lo_rate = str_replace('##', '', $lo_rate);
                    $lo_rate = str_replace('Lowest#Rated', '', $lo_rate);
                    $lo_rate = str_replace('#', ' ', $lo_rate);
                    $lo_rate = substr($lo_rate, 1);
                    $celeb['lowest_rate'] = $lo_rate;
                    #echo $lo_rate . "\n";
                }
                elseif (strpos($div_text, 'Birthday:') !== false){
                    $birthday = preg_replace('/[^a-zA-Z0-9,]/', '', $div_text);
                    //$birthday = preg_replace('/\s+/', '', $birthday);
                    $birthday = str_replace('Birthday', '', $birthday);
                    $celeb['birthday'] = $birthday;
                    #echo $birthday . "\n";

                }elseif(strpos($div_text, 'Birthplace:') !== false){
                    $birthplace = preg_replace('/[^a-zA-Z0-9,]/', '', $div_text);
                    //$birthplace = preg_replace('/\s+/', '', $birthplace);
                    $birthplace = str_replace('Birthplace', '', $birthplace);
                    $celeb['birthplace'] = $birthplace;
                    #echo $birthplace . "\n";
                }
            }

            if ($current_div->getAttribute('class') == 'celeb_summary_bio clamp clamp-5'){
                $has_bio = true;
                $div_text = $current_div->textContent;
                $div_text = str_replace('  ', '', $div_text);
                $celeb['info'] = $div_text;
                #echo $div_text . "\n";
            }

            if ($current_div->getAttribute('class') == 'celebHeroImage'){
                $image_url = $current_div->getAttribute('style');
                $image_url = str_replace("background-image:url('", '', $image_url);
                $image_url = str_replace("')", '', $image_url);
                $celeb['image'] = $image_url;
                #echo $image_url . "\n";
            }
        }

        if ($has_bio == false){
            $celeb['info'] = " ";
            #echo 'bio not available' . "\n";
        }
        $celeb['url'] = $url;
        $celeb = Celeb::create($celeb);

        return $celeb;
    }
}
