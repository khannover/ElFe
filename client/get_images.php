<?php

$outpath="/home/pi/imgs";
$token="d73b04b0e696b0945283defa3eee4538";
$slideshow='sudo fbi -m "1920x1080-60" --autoup -a -u -t "10" -noverbose -readahead -blend 2000 -d /dev/fb0 -T 2 "/home/pi/imgs"/*.*';
$list_json=file_get_contents('http://khannover.mooo.com:8080/elternfeed/api.php?action=list&token=' . $token);
$list = json_decode($list_json);
if(isset($argv[1]))
  $arg = $argv[1];
else
  $arg = null;
$tmppath="/tmpfs";

if(isset($arg) && $arg == "restart"){
	shell_exec("sudo pkill fbi; $slideshow");
	exit("1");
}
if(isset($list->cleanup) && $list->cleanup == true ){
	shell_exec("rm $outpath/*.jpg $outpath/*.png");
}
if(!isset($list->error)){
	foreach($list as $image){
	  print_r($image);
	  if(isset($image->filename)){
		  $img_bin=file_get_contents('http://khannover.mooo.com:8080/elternfeed/api.php?action=get&file=' . $image->filename . '&token=' . $token);
		  file_put_contents("$tmppath/" . $image->filename, $img_bin);
		  if(isset($image->description)){
		  	file_put_contents("$outpath/" . $image->filename, annotateImg($tmppath . "/" . $image->filename, $image->description));
		  	unlink($tmppath . "/" . $image->filename);
		  }
		  if(file_exists("$outpath/" . $image->filename)){
		    $result = file_get_contents('http://khannover.mooo.com:8080/elternfeed/api.php?action=received&file=' . $image->filename . '&token=' . $token);
		    echo $result;
		  }
	  }
	}
	shell_exec("sudo pkill fbi; $slideshow");
}else{
	echo "Error: " . $list->error;
}

function annotateImg($file, $txt){
	$image = new Imagick($file);
	
	// Watermark text
	$text = $txt;
	
	// Create a new drawing palette
	$draw = new ImagickDraw();
	
	// Set font properties
	//$draw->setFont('Arial');
	$draw->setFontSize(80);
	$draw->setFillColor('black');
	$draw->setFontWeight(800);
	
	// Position text at the bottom-right of the image
	$draw->setGravity(Imagick::GRAVITY_SOUTHEAST);
	
	// Draw text on the image
	$image->annotateImage($draw, 10, 12, 0, $text);
	
	// Draw text again slightly offset with a different color
	$draw->setFillColor('red');
	$image->annotateImage($draw, 11, 11, 0, $text);
	
	// Set output image format
	$image->setImageFormat('png');
	
	// Output the new image
	return $image;;
}
?>

