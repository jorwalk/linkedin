<?php
use LinkedIn\LinkedIn;
require ($_SERVER['DOCUMENT_ROOT'] . "/linkedin/LinkedIn/LinkedIn.php");

// in order to save the token
session_start();

// define api details
$details = new stdClass;
$details->api_key = '785zve3k0px387';
$details->api_secret = 'T5D8ChAalt1LSGgd';

// define and instantiate LinkedIn class with config details
$li = new LinkedIn(array('api_key' => $details->api_key, 'api_secret' => $details->api_secret, 'callback_url' => 'http://localhost/linkedin/'));

// define the scope with which the classes uses
$url = $li->getLoginUrl(array(LinkedIn::SCOPE_BASIC_PROFILE, LinkedIn::SCOPE_FULL_PROFILE, LinkedIn::SCOPE_EMAIL_ADDRESS, LinkedIn::SCOPE_NETWORK));

// if the request has the code
if (isset($_REQUEST['code'])):
    $token = $li->getAccessToken($_REQUEST['code']);
    $token_expires = $li->getAccessTokenExpiration();
    $_SESSION['li_token'] = $token;
    header('Refresh: 1;url=http://localhost/linkedin');
endif;

// check to see if there is an access token
if (!$li->hasAccessToken()):
    echo "<a href=" . $url . ">SIGN IN</a>";
endif;

// if the session has the token
if (isset($_SESSION['li_token'])):
    $token = $_SESSION['li_token'];
    $li->setAccessToken($token);
endif;

// if the class has an access token
// remove == null to make request out to LI
if ($li->hasAccessToken() == null):
    
    // id
    // first-name
    // last-name
    // maiden-name
    // formatted-name
    // phonetic-first-name
    // phonetic-last-name
    // formatted-phonetic-name
    // headline
    // location:(name)
    // location:(country:(code))
    // industry
    // distance
    // relation-to-viewer:(distance)
    // relation-to-viewer:(related-connections)
    // current-share
    // num-connections
    // num-connections-capped
    // summary
    // specialties
    // positions
    // picture-url
    // site-standard-profile-request
    // api-standard-profile-request:(url)
    // api-standard-profile-request:(headers)
    // public-profile-url
    $basic_info = $li->get('/people/~:(id,first-name,last-name,maiden-name,formatted-name,phonetic-first-name,phonetic-last-name,formatted-phonetic-name,headline,location:(name,country:(code)),industry,distance,relation-to-viewer:(distance,related-connections),current-share,num-connections,num-connections-capped,summary,specialties,positions,picture-url,site-standard-profile-request,api-standard-profile-request:(url,headers),public-profile-url)');
    $connections = $li->get('/people/~/connections:(id,first-name,last-name,headline,location:(name,country:(code)),industry,distance,num-connections,positions,picture-url,public-profile-url)');
    
    // digest returned connection results set
    $groups = array();
    foreach ($connections["values"] as $key => $connection):
        $collection = new stdClass;
        $collection->id = $connection["id"];
        $collection->first = $connection["firstName"];
        $collection->last = $connection["lastName"];
        
        if (isset($connection["headline"])):
            $collection->headline = $connection["headline"];
        else:
            $collection->headline = "";
        endif;
        
        if (isset($connection["location"])):
            $collection->country_code = $connection["location"]["country"]["code"];
            $collection->location_name = $connection["location"]["name"];
        else:
            $collection->country_code = "";
            $collection->location_name = "";
        endif;
        
        if (isset($connection["numConnections"])):
            $collection->num_connections = $connection["numConnections"];
        else:
        	$collection->num_connections = "";
        endif;
        
        if (isset($connection["distance"])):
            $collection->distance = $connection["distance"];
        else:
            $collection->distance = "";
        endif;
        
        if (isset($connection["industry"])):
            $collection->industry = $connection["industry"];
        else:
            $collection->industry = "";
        endif;
        
        if (isset($connection["pictureUrl"])):
            $collection->picture_url = $connection["pictureUrl"];
        else:
            $collection->picture_url = "";
        endif;
        
        if (isset($connection["publicProfileUrl"])):
            $collection->public_profile_url = $connection["publicProfileUrl"];
        else:
            $collection->public_profile_url = "";
        endif;
        
        if (isset($connection["positions"])):
            $collection->positions_total = $connection["positions"]["_total"];
            $collection->positions = array();
            
            // are there positions values
            if (isset($connection["positions"]["values"])):
                $count = count($connection["positions"]["values"]);
                if ($count > 0):
                    foreach ($connection["positions"]["values"] as $key => $value):
                        $position = new stdClass;
                        $position->id = $value["id"];
                        $position->is_current = $value["isCurrent"];
                        
                        foreach ($value["company"] as $key => $val):
                            if ($key === "id"):
                                $position->company_id = $val;
                            endif;
                            
                            if ($key === "name"):
                                $position->company_name = $val;
                            endif;
                        endforeach;
                        
                        if (isset($value["startDate"])):
                            foreach ($value["startDate"] as $key => $val):
                                if ($key === "month"):
                                    $position->start_date_month = $val;
                                endif;
                                
                                if ($key === "year"):
                                    $position->start_date_year = $val;
                                endif;
                            endforeach;
                        endif;
                        
                        if (isset($value["summary"])):
                            $position->summary = $value["summary"];
                        endif;
                        
                        $position->title = $value["title"];
                        
                        $collection->positions[] = $position;
                    endforeach;
                endif;
            else:
                $collection->positions = array();
            endif;
        else:
            $collection->positions_total = "";
        endif;
        
        $groups[] = $collection;
    endforeach;
    
    // save json file of groups for the user
    $filename = str_replace(" ", "_", strtolower($basic_info["formattedName"]));
    $fp = fopen($filename . '.json', 'w');
    fwrite($fp, json_encode($groups));
    fclose($fp);
endif;

// demo
// open the json file and decode
$filename = "jordan_walker";
$file = file_get_contents($filename.'.json');
$decode = json_decode($file);
echo "<pre>";
//print_r($decode);

//
function num_connections($a,$b)
{
    if ($a->num_connections == $b->num_connections) {
        return 0;
    }
    return ($a->num_connections > $b->num_connections) ? -1 : 1;
}

uasort($decode,"num_connections");
print_r(array_values($decode));

?>

 
