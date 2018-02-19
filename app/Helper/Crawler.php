<?php
/**
 * Created by PhpStorm.
 * User: Mig
 * Date: 2/17/18
 * Time: 8:26
 */

class Crawler {
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

    public function insertData($movie, $conn){
        $sql = "INSERT INTO movie (name, year, rating, genre, director, writer, runtime, critics_score, audience_score, fresh_rotten, info, poster, created_at, updated_at)";
        $value = "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
        $value = sprintf($value,
            $movie['name'], $movie['year'],
            $movie['rating'], $movie['genre'],
            $movie['director'], $movie['writer'],
            $movie['runtime'], $movie['critics_score'],
            $movie['audience_score'], $movie['fresh_rotten'],
            $movie['info'], $movie['poster'],
            date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
        );
        $sql .= $value;

        if ($conn->query($sql) === TRUE) {
            #echo $movie['name'] . " created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    public function check_exist($movie_name, $conn){
        $sql = "SELECT name FROM movie WHERE name='" . $movie_name . "';";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getMovie(){
        $servername = "localhost";
        $username = "root";
        $password = "mig39525G";
        $dbname = "fresh_tomato";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

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
                $curr_movie_url = 'https://www.rottentomatoes.com'.$current_link;
                echo $current_link;

                $html = $this->sendGetRequest($curr_movie_url, $curl);
                $movie = [];
                @$dom->loadHTML($html);
                $dom->preserveWhiteSpace = false;
                $movie_title = $dom->getElementById('movie-title');
                $movie['name'] = trim(preg_replace('/\s+/', ' ', $movie_title->textContent));
                if($this->check_exist($movie['name'], $conn) == true){
                    continue;
                }

                $year = $movie_title->childNodes->item(1)->textContent;
                $year = str_replace('(', '', $year);
                $year = str_replace(')', '', $year);
                $movie['year'] = (int)$year;

                $critic = $dom->getElementById('all-critics-numbers');
                $critic_score = $critic->getElementsByTagName('span')->item(1)->textContent;
                $movie['critics_score'] = $critic_score;

                $fresh = $critic->getElementsByTagName('span')->item(7)->textContent;
                $rotten = $critic->getElementsByTagName('span')->item(9)->textContent;
                $movie['fresh_rotten'] = $fresh . ':' . $rotten;

                $image_section = $dom->getElementById('movie-image-section');
                $img = $image_section->getElementsByTagName('img');
                $poster_url = $img->item(0)->getAttribute('src');
                $movie['poster'] = $poster_url;

                $all_div = $dom->getElementsByTagName('div');
                $all_div_count = $dom->getElementsByTagName('div')->length;
                for ($i = 0; $i < $all_div_count; $i++){
                    if ($all_div->item($i)->getAttribute('class') == 'audience-score meter'){
                        $audience_score =  $all_div->item($i)->textContent;
                        $audience_score = str_replace('liked it', '', $audience_score);
                        $audience_score = str_replace('\n', '', $audience_score);
                        $audience_score = trim(preg_replace('/\s+/', ' ', $audience_score));
                        $movie['audience_score'] = $audience_score;
                    }
                }

                $info = $dom->getElementById('movieSynopsis');
                $info = $info->textContent;
                $info = str_replace(',', '|', $info);
                $info = trim(preg_replace('/\s+/', ' ', $info));
                $info = str_replace("'", '*', $info);
                $movie['info'] = $info;

                $mv_main_container = $dom->getElementById('main_container');
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
                        $movie['director'] = $director;
                    }elseif ($div->item($i)->textContent == 'Written By: '){
                        $writer = $div->item($i + 1)->textContent;
                        $writer = str_replace(',', '|', $writer);
                        $writer = trim(preg_replace('/\s+/', ' ', $writer));
                        $movie['writer'] = $writer;
                    }elseif ($div->item($i)->textContent == 'Runtime: '){
                        $runTime = $div->item($i + 1)->textContent;
                        $runTime = str_replace(' minutes', '', $runTime);
                        $movie['runtime'] = (int) $runTime;
                    }
                }
                $this->insertData($movie, $conn);
                $count++;
            }
        }
        # echo $count;
        $conn->close();
        curl_close($curl);
    }
}