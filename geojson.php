<?php
header("Access-Control-Allow-Origin: *");
//TEST FILE - NOT FOR PRODUCTION
//header('Content-disposition: attachment; filename=geoFile.geojson');
   //   header('Content-Type: application/json');
    // Disable caching
  //  header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
  //  header("Pragma: no-cache"); // HTTP 1.0
  //  header("Expires: 0"); // Proxies


require 'connect.php';
//file with connection information
//setup like:
//$server="server";
//$user="username";
//$password="password";


//strict
function make_safe($string) {
    $string = preg_replace("/[^A-Za-z0-9, \-.]/", '', $string);
    return $string;
}

//$GET Variables

//potential multi select (comma delimited list)
if (isset($_GET['field'])){$field = make_safe($_GET['field']);} //comma delimited list
if (isset($_GET['state'])){$state = make_safe($_GET['state']);}
if (isset($_GET['county'])){$county = make_safe($_GET['county']);}
if (isset($_GET['geonum'])){$geonum = make_safe($_GET['geonum']);} //comma delimited list
if (isset($_GET['geoid'])){$geoid = make_safe($_GET['geoid']);}
if (isset($_GET['table'])){$table = make_safe($_GET['table']);} //comma delimited list


//single select
if (isset($_GET['sumlev'])){$sumlev = make_safe($_GET['sumlev']);}  //required for geojson
if (isset($_GET['db'])){$db = make_safe($_GET['db']);}else{$db='acs1014';} //if no db given, assume most current
//set default for schema if it is missing
if (isset($_GET['schema'])){$schema = make_safe($_GET['schema']);}else{
     if($db=='acs1014'){$schema='data';}elseif // for example, acs1014 defaults to data 
  ($db=='acs0913'){$schema='data';}elseif // for example, acs0913 defaults to data 
  ($db=='acs0812'){$schema='data';}elseif // for example, acs0812 defaults to data
  ($db=='c2010'){$schema='data';}elseif // c2010 defaults to data
  ($db=='c2000'){$schema='sf1';}elseif // c1990 defaults to sf1
  ($db=='c1990'){$schema='sf1';}elseif
  ($db=='c1980'){$schema='sf1';}else{$schema='';} //no valid schema - will deal with later
}

//carto or tiger or nhgis
$geo=""; //for now, geo will be set as a default

  if($db=='acs1014'){$geo='carto';}elseif // for example, acs0812 defaults to data
  ($db=='acs0913'){$geo='carto';}elseif // for example, acs0812 defaults to data  
  ($db=='acs0812'){$geo='carto';}elseif // for example, acs0812 defaults to data    
  ($db=='c2010'){$geo='carto';}elseif // c2010 defaults to data
  ($db=='c2000'){$geo='carto';}elseif // c1990 defaults to sf1
  ($db=='c1990'){$geo='nhgis';}elseif
  ($db=='c1980'){$geo='nhgis';}else{$geo='';} //no valid database - will deal with later

//carto or tiger or nhgis
$geodesc=""; //for now, geodesc will be set based upon sumlev


  if($sumlev=='160'){$geodesc='place';}elseif // for example, acs0812 defaults to data
  ($sumlev=='150'){$geodesc='bg';}elseif // c2010 defaults to data
  ($sumlev=='140'){$geodesc='tract';}elseif // c1990 defaults to sf1
  ($sumlev=='50'){$geodesc='county';}elseif
  ($sumlev=='40'){$geodesc='state';}else{
      //see geonum / geoid section for assigning $geodesc
  } //no sumlev


  $limit=100;  //by default limits to 100 search results.  override by setting limit= in GET string
if (isset($_GET['limit'])){$limit = make_safe($_GET['limit']);}

//if database is acs0812, check to see if moe option is flagged
$moe='no';
if($db=='acs0812' or $db=='acs0913' or $db=='acs1014'){if (isset($_GET['moe'])){$moe=make_safe($_GET['moe']);}}

//modify port if asking for newest geo - from acs1014
$port='5432';
if($db=='acs1014'){$port='5433';}else{$port='5432';}

//variables and arrays to use later
  $tablelist=[]; //array of all tables used in query
  $jointablelist=""; //working string of tables to be inserted into sql query
  $joinlist="";  //working string of 'where' condition to be inserted into sql query
  $arr=[];
  $arr2=[];
  $tcolumns=[]; //columns gathered from table(s?)
$zoom=0;  //map zoom level
$tolerance=0;  //for simplifying geometry
$moefields=[];

if (isset($_GET['zoom'])){$zoom=make_safe($_GET['zoom']);}else{$zoom=16;}


if($zoom==2){$tolerance=0.2;}
if($zoom==3){$tolerance=0.1;}
if($zoom==4){$tolerance=0.07;}
if($zoom==5){$tolerance=0.04;}
if($zoom==6){$tolerance=0.018;}
if($zoom==7){$tolerance=0.01;}
if($zoom==8){$tolerance=0.005;}
if($zoom==9){$tolerance=0.003;}
if($zoom==10){$tolerance=0.0015;}
if($zoom==11){$tolerance=0.001;}
if($zoom==12){$tolerance=0.0005;}
if($zoom==13){$tolerance=0.00025;}
if($zoom==14){$tolerance=0.0001;}
if($zoom==15){$tolerance=0.0001;}
if($zoom==16){$tolerance=0.0001;}


  //if no fields or tables are selected
// if (!(isset($_GET['table'])) && !(isset($_GET['field']))){goto a;}


//echo $_SERVER['SERVER_ADDR'];
// attempt a connection
$dbh = pg_connect("host=".$server." port=".$port." dbname=".$db." user=".$user." password=".$password);

if (!$dbh) {
  echo 'terminating';
    die("Error in connection: " . pg_last_error());
}


  //if no fields are selected (then a table must be).  Create fields list based on the given table.
if (!(isset($_GET['field']))){
  
if (isset($_GET['table'])){
    
  $atablearray=explode(",", $table);
  $atablestr="";
  
  foreach($atablearray as $ata){
          $atablestr=$atablestr." table_name='".$ata."' or";    
  }
  
    //trim last trailing 'or'
  $atablestr=substr($atablestr,0,-2);
  
  
  //STOP: Don't Query DB for this!!! Too Slow!!!
    //Query table fields 
  $tablesql="SELECT column_name from information_schema.columns 
where (".$atablestr.") and table_schema='".$schema."';";

  $tableresult = pg_query($dbh, $tablesql);
  
while ($tablerow = pg_fetch_array($tableresult)) {

  if($tablerow['column_name']<>'geonum'){array_push($tcolumns, $tablerow['column_name']);}
  
  }
  
  $field = implode(',', $tcolumns); //$field becomes fields queried from info schema based upon table

}
}



  //break the comma delimited records from field into an array  
$ttlfields=explode(",", $field);
  
    //if moe is set to yes, add the moe version of each field (push into new array, then merge with existing)
  if($moe=='yes'){

    foreach($ttlfields as $tmoe){
      //if text _moe doesn't already occur in the field name
      $pos=strpos($tmoe,'_moe');
      if($pos === false){
        array_push($moefields, substr_replace($tmoe, '_moe', -3, 0));
      }
    }
    $ttlfields = array_merge($ttlfields, $moefields);

    //remove duplicate field names
    $ttlfields=array_unique($ttlfields); 
  
  //send moe modified field list back to main field list
  $field = implode(',', $ttlfields);
  }
  
//get a list of tables based upon characters in each field name  (convention: last 3 characters identify field number, previous characters are table name) 
    foreach($ttlfields as $t){
      array_push($tablelist, substr($t,0,-3));
    }

  //remove duplicate tables in array
    $tablelist=array_unique($tablelist);
  
  //create a string to add to sql statement
    foreach($tablelist as $t){  
      $jointablelist=$jointablelist." natural join ".$schema.".".$t;
    }



//here's where you figure out what case situation you're in

//CASE 1:  you have a geonum
//essentially you don't care about anything else.  just get the data for that/those geonum(s)
if (isset($geonum)){
  

    //break the comma delimited records from geonum into an array  
  $geonumarray=explode(",", $geonum);
  

  //quick sidestep, calculate number of digits in geonum to assign join table geography
  if(strlen($geonumarray[0])==3){$geodesc='state';}
  if(strlen($geonumarray[0])==6){$geodesc='county';}
  if(strlen($geonumarray[0])==8){$geodesc='place';}
  if(strlen($geonumarray[0])==12){$geodesc='tract';}
  if(strlen($geonumarray[0])==13){$geodesc='bg';}  
  
//iterate through all geonum's
foreach ($geonumarray as $geonumlist){
      $joinlist=$joinlist." geonum=".$geonumlist." or";
}
  
  //trim last trailing 'or'
  $joinlist=substr($joinlist,0,-2);
  
//END CASE 1
}elseif (isset($geoid)) {
//CASE 2:  you have a geoid
  
    
      //break the comma delimited records from geonum into an array  
  $geoidarray=explode(",", $geoid);
      
  //quick sidestep, calculate number of digits in geoid to assign join table geography
  if(strlen($geonumarray[0])==3){$geodesc='state';}
  if(strlen($geonumarray[0])==6){$geodesc='county';}
  if(strlen($geonumarray[0])==8){$geodesc='place';}
  if(strlen($geonumarray[0])==12){$geodesc='tract';}
  if(strlen($geonumarray[0])==13){$geodesc='bg';}  
  
//iterate through all geoids, simply put a '1' in front and treat them like geonums
foreach ($geoidarray as $geoidlist){
      $joinlist=$joinlist." geonum=1".$geoidlist." or";
}
  
  //trim last trailing 'or'
  $joinlist=substr($joinlist,0,-2);
 
//END CASE 2  
}elseif ((isset($county) || isset($state))){
  //CASE 3 - query
  
  $condition=""; //condition is going to be a 2 character string which identifies county, state (yes/no) (1,0)
  if(isset($county)){$condition="1";}else{$condition="0";}
  if(isset($state)){$condition=$condition."1";}else{$condition=$condition."0";}

  
   
  if(isset($county)){
//create county array out of delimited list
    $countylist="";
  
    //break the comma delimited records from county into an array  
  $countyarray=explode(",", $county);
  
//iterate through all counties
foreach ($countyarray as $carray){
      $countylist=$countylist." county=".$carray." or";
}
  
  //trim last trailing 'or'
  $countylist=substr($countylist,0,-2);
  }
  
  
   if(isset($state)){
//create state array out of delimited list
      $statelist="";
  
    //break the comma delimited records from county into an array  
  $statearray=explode(",", $state);
  
//iterate through all states
foreach ($statearray as $starray){
      $statelist=$statelist." state=".$starray." or";
}
  
  //trim last trailing 'or'
  $statelist=substr($statelist,0,-2);
   }  
  

   
  //every possible combination of county, state
  if($condition=='001'){$joinlist = " (".$statelist.") ";}
  if($condition=='011'){$joinlist = " (".$countylist.") and (".$statelist.") ";}
  if($condition=='010'){$joinlist = " (".$countylist.") ";}
  
  //END CASE 3
}else if(isset($sumlev)){
  //CASE 4: Only Sumlev
  $joinlist = " 5=5 "; //nonsense here because of preceding 'AND'
  //END CASE 4
}else{
  // CASE 5: No Geo
  goto a;
//END CASE 5
}


$bbstr=""; //bounding box string

//potential single select
if (isset($_GET['bb'])){
  $bb = make_safe($_GET['bb']);
$bbstr=$geo.".".$geodesc.".geom && ST_MakeEnvelope(".$bb.", 4326) and ";
}  //bounding box example: "-105,40,-104,39" no spaces no quotes



  //CONSTRUCT MAIN SQL STATEMENT
// execute query
$sql = "SELECT geoname, geonum, ".$field.", st_asgeojson(st_transform(ST_Simplify(" . pg_escape_string('geom') . ",".$tolerance."),4326)) AS geojson from ".$geo.".".$geodesc." ".$jointablelist." where ".$bbstr." ".$joinlist." limit $limit;";


$result = pg_query($dbh, $sql);


  //flag error
if (!$result) {
    die("Error in SQL query: " . pg_last_error());
}


# Build GeoJSON
$output    = '';
$rowOutput = '';

while ($row = pg_fetch_assoc($result)) {
    $rowOutput = (strlen($rowOutput) > 0 ? ',' : '') . '{"type": "Feature", "geometry": ' . $row['geojson'] . ', "properties": {';
    $props = '';
    $id    = '';
    foreach ($row as $key => $val) {
        if ($key != "geojson") {
            $props .= (strlen($props) > 0 ? ',' : '') . '"' . $key . '":"' . escapeJsonString($val) . '"';
        }
        if ($key == "id") {
            $id .= ',"id":"' . escapeJsonString($val) . '"';
        }
    }
    
    $rowOutput .= $props . '}';
    $rowOutput .= $id;
    $rowOutput .= '}';
    $output .= $rowOutput;
}

$output = '{ "type": "FeatureCollection", "features": [ ' . $output . ' ]}';
echo $output;


a: {  

  
};



//supporting functions
function escapeJsonString($value) { # list from www.json.org: (\b backspace, \f formfeed)
  $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
  $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
  $result = str_replace($escapers, $replacements, $value);
  return $result;
}



?>