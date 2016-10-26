<?php
require_once("token.php");
$folder_todeliver = dirname(__FILE__) . "/todeliver";
$folder_delivered = dirname(__FILE__) . "/delivered";
$prot = empty($_SERVER['HTTPS']) ? "http://" : "https://";
$action = empty($_GET['action']) ? "list" : $_GET['action'];

function hasAccess($token, $rights){
  if(in_array($token, $rights)){
    return TRUE;
  }else{
    return FALSE;
  }
}

function getFiles($path){
  global $prot;
	if ($handle = opendir($path)) {
	    $count = 1;
	    $images = array();
	    while (false !== ($entry = readdir($handle))) {
		    if(file_exists("$path/cleanup.txt")){
			$images["cleanup"] = "true";
		    }
		    if($entry != "." && $entry != ".." && strpos($entry , ".htaccess") === FALSE && strpos($entry , ".txt") === FALSE ){
			    $images["image-$count"]["filename"] = "$entry";
			    if(file_exists("$path/$entry.txt")){
				$description = rtrim(file_get_contents("$path/$entry.txt"));
			    }else{
				$description = "";
			    }
			    $images["image-$count"]["description"] = "$description";
		            $count++;  
		    }
	    }
            closedir($handle);
            return $images;
	}
}

function move($file){
  global $folder_todeliver;
  global $folder_delivered;
  if(file_exists("$folder_todeliver/$file")){
    rename("$folder_todeliver/$file", "$folder_delivered/" .date("Ymd-His") . "-$file");
    if(file_exists("$folder_todeliver/$file.txt")){
      rename("$folder_todeliver/$file", "$folder_delivered/" .date("Ymd-His") . "-$file.txt");
    }
    echo json_encode(array( "result" => "success"));
  }else{
    if(!empty($delivered = glob("$folder_delivered/*-$file"))){
      echo json_encode(array( "result" => "already delivered", "versions" => sizeof($delivered)));
    }else{
      echo json_encode(array( "result" => "failed"));
    }
  } 
}

function deliver($filename){
  if(file_exists($filename)){

    //Get file type and set it as Content Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    header('Content-Type: ' . finfo_file($finfo, $filename));
    finfo_close($finfo);

    //Use Content-Disposition: attachment to specify the filename
    header('Content-Disposition: attachment; filename='.basename($filename));

    //No cache
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    //Define file size
    header('Content-Length: ' . filesize($filename));

    ob_clean();
    flush();
    readfile($filename);
    exit; 
  }else{
    echo json_encode(array( "error" => "File does not exist!" ));
  }
}

if(!hasAccess($_GET['token'], $users)){
  echo json_encode(array("error"=>"token invalid"));
  exit();
}

switch($action){
  case "list": 
    $list = empty(getFiles($folder_todeliver)) ? array("error" => "no files to deliver") : getFiles($folder_todeliver);
    echo json_encode($list);
    break;
  case "get":
    if(!empty($_GET['file'])){
      deliver("todeliver/" . str_replace("\"","",str_replace("..","",$_GET['file'])));
    }else{
      echo json_encode(array( "error" => "File missing!"));
    }
    break;
  case "received":
    if(!empty($_GET['file'])){
      move(str_replace("..", "", $_GET['file']));
      file_put_contents("/wwwroot/elternfeed/log.txt", date("Ymd His") . ": " . $_GET['file'] . " was delivered to " . $_SERVER['REMOTE_ADDR'], FILE_APPEND);
    }else{
      echo json_encode(array( "error" => "File missing!"));
    }
    break;
  case "admin":
    if(hasAccess($_GET['token'], $admins)){
      echo json_encode(array("result"=>"success"));
    }else{
      echo json_encode(array("error"=>"wrong token"));
    }
    break;
}
