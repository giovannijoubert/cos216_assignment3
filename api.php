<?php 

class Database{ //connection to database to confirm API key
    public static function instance()
    {
        static $instance = null;
        if($instance === null) $instance = new Database();
        return $instance;
    }
    
    // Database Detail
    private $host = "wheatley.cs.up.ac.za";
    private $db_name = "u18009035_COS216";
    private $username = "u18009035";
    private $password = "G10v@nn1";
    private $conn;
    
    private function __construct(){
        $this->conn = null;
    }
    
    public function connectDB(){
        //Connect
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            return true;
        }catch(PDOException $exception){
            return false;
        }
    }
    
    public function __destruct(){
        $this->conn = null;
    }
    
    public function checkAPI($uAPIKey){
        //Check whether uAPIKey exists in Database
        $sql = "SELECT count(*) FROM Users WHERE uAPIKey = '$uAPIKey'"; 
        $result = $this->conn->query($sql); 
        $number_of_rows = $result->fetchColumn(); 
        
        //if uAPIKey exists, return connection, else return false
        if($number_of_rows>0){
           return true; 
        } else {
            return false;
        }
    }   
}

class AggregatorAPI{
    private $reqTitle;
    private $reqIMDBID;
    private $return;
    
    public function __construct(){
        header("Content-Type: application/json"); //ALWAYS return JSON    
    }
    
    public function processInfoRequest($reqTitle, $reqIMDBID, $return){
    if($return == false){
        $localError = true;
    } else {
    
    $localError = false;
   
    if($reqIMDBID != false){ //Favor given to IMDBID if provided
        $OMDB_RES = array();
        $OMDB_SINGLE_RES = $this->cURLRetrieve("http://www.omdbapi.com/?i=$reqIMDBID&apikey=d3422035");
        if ($OMDB_SINGLE_RES != false)
            array_push($OMDB_RES, $OMDB_SINGLE_RES);
    } else if ($reqTitle != false){ //Else do search by title
        if ($reqTitle == "*"){ //accomodate for wildcard title search
            $TMDB_RES = $this->cURLRetrieve("https://api.themoviedb.org/3/discover/movie?api_key=3f51c3329a20300b741b815efe1b5daa&language=en-US&sort_by=popularity.desc&include_adult=false&include_video=false&page=1");
            $OMDB_RES = $this->getOMDBFromTMDB($TMDB_RES);
        } else { 
            $TMDB_RES = $this->cURLRetrieve("https://api.themoviedb.org/3/search/movie?api_key=3f51c3329a20300b741b815efe1b5daa&language=en-US&query=$reqTitle&page=1&include_adult=false");
            $OMDB_RES = $this->getOMDBFromTMDB($TMDB_RES);
        }
    } else {
        $localError = true;
        $TMDB_RES = false;
        $OMDB_RES = false;
    }
    }
     
    $this->processInfoResponse($OMDB_RES, $localError, $return);
    
    }
    
    private function getOMDBFromTMDB($TMDB_RES){
    $OMDB_RES = array();
            if($TMDB_RES != false){
                $TMDB_RES = json_decode($TMDB_RES);
                foreach ($TMDB_RES->results as $result) {
                    $OMDB_SINGLE_RES = $this->cURLRetrieve("http://www.omdbapi.com/?t=".urlencode($result->title)."&apikey=d3422035");
                    if($OMDB_SINGLE_RES == false){
                        $OMDB_RES = false;
                        break;
                    }
                    array_push($OMDB_RES, $OMDB_SINGLE_RES);       
                }
            }
    return $OMDB_RES;
    }
    
    public function processInfoResponse($OMDB_RES, $localError, $return){
    $date = new DateTime(); //get date for timestamp
    if($localError == true){ //local error
        $response_data  = array(
            'status'    => 'unsuccessful',
            'timestamp' => $date->getTimestamp(),
            'data'      => array()
        );
    } else if ($OMDB_RES == false){ //external error, one or more sources failed
        $response_data  = array(
            'status'    => 'error',
            'timestamp' => $date->getTimestamp(),
            'data'      => array()
        );
    } else { //success, prepare and return data
        $response_data  =   array(
            'status'    =>  'success',
            'timestamp' =>  $date->getTimestamp(),
            'data'      =>  array()
        );
        
        foreach($OMDB_RES as $result){
            $SINGE_RES =  $this->processReturn($result, $return);
            if($SINGE_RES)
                array_push($response_data["data"],$SINGE_RES);
            
        }
         unset($result);    
        
        if(empty($response_data["data"])) //check if no movies found for paramaters
             array_push($response_data["data"],"no results");
    }
        
    $response_data = json_encode($response_data);
    echo $response_data;
    }

    public function processReturn($result, $return){
    
            $result = json_decode($result);
            $movieResult  = array();
            if(isset($result->Title)){
            if($return == "*") //wild card     
            {
                
                $movieResult += array("title"      => $result->Title);
                $movieResult += array("poster"     => $result->Poster);
                $movieResult += array("year"       => $result->Year);
                $movieResult += array("released"   => $result->Released);
                $movieResult += array("synopsis"   => $result->Plot);
                $movieResult += array("imdbid"     => $result->imdbID);
                $movieResult += array("imdbRating" => $result->imdbRating);
            } else {
              //  var_dump($return);
                foreach($return as $r){
                    switch ($r) {
                        case "title":
                            $movieResult += array("title"      => $result->Title);
                            break;
                        case "poster":
                            $movieResult += array("poster"     => $result->Poster);
                            break;
                        case "year":
                            $movieResult += array("year"       => $result->Year);
                            break;
                        case "released":
                            $movieResult += array("released"   => $result->Released);
                            break;
                        case "synopsis":
                            $movieResult += array("synopsis"   => $result->Plot);
                            break;
                        case "imdbid":
                            $movieResult += array("imdbid"     => $result->imdbID);
                            break;
                        case "imdbRating":
                            $movieResult += array("imdbRating" => $result->imdbRating);
                            break;
                    }
                }
            }
                return $movieResult;
        }
    }

    private function cURLRetrieve($externalURL){    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $externalURL,
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_PROXY => "phugeet.cs.up.ac.za:3128", UNCOMMENT ON WHEATLEY
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return $response;
        }
    }
    

    
    public function endWithLocalError($error){
        $date = new DateTime();
        $response_data  = array(
            'status'    => 'unsuccessful',
            'error'     => $error,
            'timestamp' => $date->getTimestamp()
        );
        $response_data = json_encode($response_data);
        echo $response_data;
        die();
    }
    
    
}

$Aggregate = new AggregatorAPI();

        //Make sure that it is a POST request.
        if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
            $Aggregate->endWithLocalError("Request method must be POST!");
        }

        //Make sure that the content type of the POST request has been set to application/json
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if(strcasecmp($contentType, 'application/json') != 0){
            $Aggregate->endWithLocalError('POST Content type must be: application/json');
        }

        //Receive the RAW post data.
        $POSTData = trim(file_get_contents("php://input"));

        //Attempt to decode the incoming RAW post data from JSON.
        $decodedData = json_decode($POSTData, true);

        //If json_decode failed, the JSON is invalid.
        if(!is_array($decodedData)){
            $Aggregate->endWithLocalError('POST Content contains invalid JSON!');
        }

        //check DB connection & SINGLETON instances
        if(Database::instance()->connectDB() == false)
            $Aggregate->endWithLocalError("Cannot connect to Database / multiple instances");
        
        //check if uAPIKey is valid
        if(isset($decodedData["request"]["key"]))
            $uAPIKey = $decodedData["request"]["key"];
        if(Database::instance()->checkAPI($uAPIKey) != true){
            $Aggregate->endWithLocalError("Invalid API Key");
        }
        
        //check if type is valid (other types only implemented in prac4)
        if($decodedData["request"]["type"] != "info")
            $Aggregate->endWithLocalError("Types other than 'info' only to be implemented in Practical 4"); 
        
        //Get data from post
        if(isset($decodedData["request"]["title"])){
            if($decodedData["request"]["title"] != "")
                $reqTitle = $decodedData["request"]["title"];
            } else {
                $reqTitle = false;
            }

        if(isset($decodedData["request"]["imdbid"])){
            if($decodedData["request"]["title"] != "")
                $reqIMDBID = $decodedData["request"]["imdbid"];
        } else {
            $reqIMDBID = false;
        }

        if(isset($decodedData["request"]["return"])){
            if($decodedData["request"]["return"] != "")
                $return = $decodedData["request"]["return"];
        } else {
             $return = false;
        }
        
        $Aggregate->processInfoRequest($reqTitle, $reqIMDBID, $return);


?>