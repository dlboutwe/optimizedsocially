<?php

require 'facebook-php-sdk-master/src/facebook.php';

$facebook = new Facebook(array(
		'appId'  => '231897623667423',
		'secret' => '33fc0dabd382889f47f1c07ad27c5c46',
));

//application permissions scope
$permissions_scope = array('scope' => 'read_stream, user_status, user_likes, basic_info, user_photos');

// Get User ID
$user_id = $facebook->getUser();

function format_auto_status($message, $objects, $user, $gender = "")
{
	//list of items that really have no substance
	$rejected_message = array("$user likes a link.", "$user likes a photo.");
	
	//check for rejected messages
	$rejected = false;
	foreach($rejected_message as $test)
	{
		if(strtolower($message) == strtolower($test))
			$rejected = true;
	}
	
	if(!$rejected)
	{
		//acting under the assumption that this is a comment you made on somebody's wall
		//with that in mind, this loop should only fire once unless you are commenting on your own status
		if($objects != null){	
			foreach($objects as $offset=>$object)
			{
				$obj = $object[0];
				$recieving_user_link = "<a target='_blank' href='https://www.facebook.com/{$obj["id"]}'>{$obj["name"]}</a>'s";			
			}
		}
		
		
		//now, get the object type (link, status, or photo)		
		if(strpos(substr($message, -9), "status") !== false)
		{
			//object is a comment on a status
			$type = "status";
		}
		elseif (strpos(substr($message, -9), "photo") !== false)
		{
			//object is a comment on a photo	
			$type = "photo";
		}
		elseif (strpos(substr($message, -9), "link") !== false)
		{
			//object is a comment on a link
			$type = "link";
		}
		elseif (strpos(substr($message, -9), "timeline") !== false)
		{
			//object is a comment on a link
			$type = "timeline";
		}
		else 
		{
			//dafuq is this?
			$type = "";
		}
		
		//now, break the actuall comment out of the aweful formatting of the feed.
		if(strpos($message, "own") === false)
		{
			$EOL = " on {$obj["name"]}'s $type.";			
		}
		else
		{
			$EOL = " on $gender own $type";
			$recieving_user_link = $gender." own";
		}
		$comment = str_replace($EOL, "", $message);
		
		
		//now that we have all of the elements, we can piece them all together
		//returns an array with [0] being the header and [1] being the message body
		if($type != "")
			return array("$user commented on $recieving_user_link $type:", str_replace("\"", "", $comment));
		else{
			
			return array("$user commented on an item:", parse_message($message, $objects, $user));
		}
	}
}


function parse_message($message, $objects, $user)
{
	$functioning_return = "";
	$current_pos = 0;
	foreach($objects as $offset=>$object)
	{
		$obj = $object[0];
		$functioning_return .= substr($message, $current_pos,  $offset - $current_pos);
		if($obj["name"] != $user){
			if($obj["type"] == "page")
				$url = "http://facebook.com/".$obj["id"];
			
			$functioning_return .= "<a target='_blank' href='$url'>{$obj["name"]}</a>";
			$current_pos = $offset + $obj["length"];
		}
	}
	$functioning_return .= substr($message, $current_pos);
	
	return $functioning_return;
}

function blurb($user, $pic, $post, $auto_generated = true)
{
	/*echo "<pre>";
	print_r($post);
	echo "</pre>";*/
	
	if($user["gender"] == "male")
		$gender_specifiers = array("his", "him");
	else
		$gender_specifiers = array("her", "her");
	
	if($auto_generated){
		switch($post["type"])
		{
			case "status":
				{
					if(strripos($post["story"], $user["name"]." likes") !== false){
						$img_link = $user["link"];
						$img_src = $pic["url"];
						$img_alt = "Profile Picture";
						$header_size = "h2";
						$heading = parse_message($post["story"], $post["story_tags"], $user["name"]);
						$content = "";
						break;
					}										
					$parsed_data = format_auto_status($post["story"], $post["story_tags"], $user["name"], $gender_specifiers[0]);
					$img_link = $user["link"];
					$img_src = $pic["url"];
					$img_alt = "Profile Picture";
					$header_size = "h2";
					$heading = $parsed_data[0];
					$content = $parsed_data[1];
				}
				break;
			case "link":
				{
					$img_link = $user["link"];
					$img_src = $pic["url"];
					$img_alt = "Profile Picture";
					$header_size = "h2";
					$heading = parse_message($post["story"], $post["story_tags"], $user["name"]);
					$content = "";
				}
				break;
			case "photo":
				{
					$img_link = $post["link"];
					$img_src = str_replace("_s.jpg","_n.jpg",$post["picture"]);;
					$img_alt = "Profile Picture";
					$header_size = "h3";
					$heading = parse_message($post["story"], $post["story_tags"], $user["name"]);
					$content = $post["caption"];
				}
				break;
		}		
	}
	else
	{
		switch($post["type"])
		{
			case "status":
				{					
					$img_link = $user["link"];
					$img_src = $pic["url"];
					$img_alt = "Profile Picture";
					$header_size = "h3";
					$heading = "{$user["name"]} updated {$gender_specifiers[0]} status:";
					$content = $post["message"];
				}
				break;
			case "link":
				{
					$img_link = $user["link"];
					$img_src = $pic["url"];
					$img_alt = "Profile Picture";
					$header_size = "h3";
					$heading = "{$user["name"]} shared a link:";
					$content = "<div class='media'>
									<a class='pull-left' href='{$post["link"]}'>
										<img class='media-object' src='{$post["picture"]}' height='64' width='64' />
									</a>
									<div class='media-body'>
										<i>{$post["description"]}</i>
									</div>
								</div><!-- end .media -->";
				}
				break;
			case "photo":
				{					
					$img_link = $post["link"];
					$img_src = str_replace("_s.jpg","_n.jpg",$post["picture"]);
					$img_alt = "Submitted Picture";
					$header_size = "h3";
					$heading = "{$user["name"]} posted a photo:";
					$content = $post["message"];
				}
		}		
	}
	echo "<div class='row'>";
		echo '<div class="col-md-8 col-md-offset-1 blurb-content">';
			echo '<div class="col-xs-12 col-md-3 col-sm-6">';
				echo "<a href='$img_link'>";
					echo "<img style='margin-bottom: 5px;' src='$img_src' alt='$img_alt' class='img-rounded img-responsive center-block'>";
				echo "</a>";
			echo "</div>";
			echo '<div class="col-xs-12 col-md-9 col-sm-6 well">';
				echo "<$header_size>$heading</$header_size>";
					echo $content;
			echo "</div>";
		echo "</div>";
	echo "</div>";
}
?>
<!Doctype html />
<html>
	<head>
		<Title>Optimized Socially</Title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script src="js/bootstrap.js"></script>
		<link rel="stylesheet" href="css/bootstrap.css" type="text/css" />
		<link rel="stylesheet" href="css/optimizesocially.css" type="text/css" />
	</head>
	<body>
	<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=231897623667423";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		<?php 
		if($user_id) {
		
		      // We have a user ID, so probably a logged in user.
		      // If not, we'll get an exception, which we handle below.
		      try {
				
		        $user_profile = $facebook->api('/me','GET');
		        $profile_pic = $facebook->api(
								    "/me/picture",
								    "GET",
								    array (
								        'redirect' => false,
								        'height' => '200',
								        'type' => 'normal',
								        'width' => '200',
								    )
								);
				$profile_pic = $profile_pic["data"];
		        $posts = $facebook->api('/me/feed','GET');
		        
		        try{
		        $path = "debug".DIRECTORY_SEPARATOR.$user_profile["name"];
		        if(!file_exists($path))
		        	mkdir($path, 0777, true);
		        file_put_contents($path.DIRECTORY_SEPARATOR."profile.php",print_r($user_profile, true));
		        file_put_contents($path.DIRECTORY_SEPARATOR."posts.php",print_r($posts, true));
		        file_put_contents($path.DIRECTORY_SEPARATOR."pic.php",print_r($profile_pic, true));
		        }
		        catch(Exception $e){
		        	echo "Failed to cache debugging data. System returned the following message:";
		        	echo "<pre>";
		        	echo $e->getMessage();
		        	echo "</pre>";
		        }
		        //echo "<pre>";
		        //print_r($user_profile);
		        //print_r($posts);
		        //print_r($profile_pic);
		        //echo "</pre>";
		        
		        if($user_profile["gender"] == "male")
		        	$gender_specifiers = array("his", "him");
		        else 
		        	$gender_specifiers = array("her", "her");
		        
		        foreach($posts["data"] as $post)
		        {		        	
		        	if($post["story"])
		        	{
		        		//if the post is an auto-generated item
		        		
		        		//list of items that really have no substance
		        		$rejected_message = array("{$user_profile["name"]} likes a link.", "{$user_profile["name"]} likes a photo.");		        		
		        		
		        		//check for rejected messages
		        		$rejected = false;
		        		foreach($rejected_message as $test)
		        		{
		        			if(strtolower($post["story"]) == strtolower($test))
		        				$rejected = true;
		        		}
		        		
		        		//if it passes the test
		        		if(!$rejected){
		        				blurb($user_profile, $profile_pic, $post, true);
		        			}
		        	}
		        	else
		        	{
		        		//if the post is user-created content
		        		blurb($user_profile, $profile_pic, $post, false);
		        	}
		        }
		
		      } catch(FacebookApiException $e) {
		        // If the user is logged out, you can have a 
		        // user ID even though the access token is invalid.
		        // In this case, we'll get an exception, so we'll
		        // just ask the user to login again here.
		        $login_url = $facebook->getLoginUrl($permissions_scope); 
		        echo 'Please <a href="' . $login_url . '">login.</a>';
		        error_log($e->getType());
		        error_log($e->getMessage());
		      }   
		    } else {
				echo "<pre>Not a facebook exception...</pre><br />";
		      // No user, print a link for the user to login
		      $login_url = $facebook->getLoginUrl($permissions_scope);
		      echo 'Please <a href="' . $login_url . '">login.</a>';
		
		    }
		
		?>
		
	</body>
</html>