<?php
const SERVER = 'http://samlinger.natmus.dk/';

if(array_key_exists('q', $_GET) && $_GET['q'] != '') {
	$searchterm = strval($_GET['q']);
} else {
	$searchterm = '';
}

error_reporting(E_ALL);

$ch = curl_init();
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

function request($url) {
	global $ch;
	$file = './cache/'.md5($url).'.service';
	if(!file_exists($file)) {
		curl_setopt( $ch, CURLOPT_URL, $url );
		$response = curl_exec($ch);
		if($response == null) {
			echo "w00t CIP errorz: " . curl_error($ch);
			exit;
		}
		$fh = fopen($file, 'w');
		fwrite($fh, $response);
		fclose($fh);
	} else {
		$response = file_get_contents($file);
	}
	return json_decode($response, true);
}

function getImageURL($id) {
	return 'crop.php?id=' . $id . '&size=200';
	
}

function printImages($maxreturned = 10, $startindex = 0) {
	global $searchterm;
	$url = SERVER . 'CIP/metadata/search/DNT/web?querystring=Original%20==%20%22Papirfoto%22&debug=1&sortby={59ac5106-a3b4-4152-8647-66cebcb6af48}:ascending&maxreturned='. $maxreturned .'&startindex=' . $startindex;
	if($searchterm) {
		echo "!!";
		$url .= '&quicksearchstring=' . urlencode($searchterm);
	}
	$images = request( $url );
	foreach($images['items'] as $image) {
		$photographer = htmlspecialchars($image['{9b071045-118c-4f42-afa1-c3121783ac66}'], ENT_QUOTES, "UTF-8");
		$description = htmlspecialchars($image['{2ce9c8eb-b83d-4a91-9d09-2141cac7de12}'], ENT_QUOTES, "UTF-8");
		$original = 'http://samlinger.natmus.dk/CIP/preview/thumbnail/DNT/' . $image['id'] . '?maxsize=500';
		$year_match = array();
		preg_match_all('|\d{4}|', $description, $year_match);
		$max_year = 0;
		foreach($year_match[0] as $year) {
			$year = intval($year);
			if($year > $max_year && $year < 2020) {
				$max_year = $year;
			}
		}
		if($max_year == 0) {
			$max_year = '';
		}
		echo "<div class='tile'><img src='" . getImageURL($image['id']) . "' data-photographer='".$photographer."' data-description='$description' data-original='$original' data-year='$max_year'><span class='year'>$max_year</span><span class='photographer'>$photographer</span></div>";
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Hack4DK 2013 - Natmus Mosaic</title>
		<meta charset="UTF-8">
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
		<style>
		@import url(http://fonts.googleapis.com/css?family=Titillium+Web);
		body {
			overflow-x: hidden;
			background-image: url('bg-texture.jpg');
			background-size: 120%;
			background-position: 40% 0;
		}
		* {
			padding: 0px;
			margin: 0px;
			font-family: 'Titillium Web', sans-serif !important;
		}
		textarea, input { outline: none; }
		
		h1 {
			position: fixed;
			bottom: 0em;
			left: 0em;
			right: 0em;
			z-index: 1050;
		}
		h1, h1 a, h1 a:hover {
			text-shadow: 0px 0px 5px rgba(0, 0, 0, 0.8);
			-webkit-text-fill-color: #F0F0F0; /* Will override color (regardless of order) */
			-webkit-text-stroke-width: 1px;
			-webkit-text-stroke-color: black;
			font-weight: bold;
			text-align: center;
			color: #FFFFFF;
			font-weight: normal;
			text-decoration: none;
		}

		form#search {
			position: fixed;
			top: 0px;
			left: 0px;
			right: 0px;
			z-index: 1100;
		}
		
		form#search input {
			position: absolute;
			font-size: 25px;
			border: 1px solid rgba(0, 0, 0, 0.6);
			padding: 0.3em;
			left: 33%;
			right: 33%;
			top: 1em;
			border-radius: 0.2em; 
			-moz-border-radius: 0.2em;
			-webkit-border-radius: 0.2em;
			text-transform: capitalize;
			opacity: 0.8;
		}
		
		form#search input:focus {
			opacity: 1.0;
		}
		
		.tile {
			position: absolute;
			display: none;
			transition: all 0.1s;
			-webkit-transition: all 0.1s; /* Safari */
			cursor: pointer;
			box-shadow: none;
			width: 180px;
			height: 180px;
		}
		.tile .year {
			position: absolute;
			top: 0;
			right: 0;
			font-size: 20pt;
			color: #E0E0E0;
			display: none;
			text-shadow: 0px 0px 4px rgba(0, 0, 0, 1);
		}
		.tile .photographer {
			position: absolute;
			bottom: 0;
			left: 0;
			font-size: 12pt;
			color: #E0E0E0;
			display: none;
			text-shadow: 0px 0px 4px rgba(0, 0, 0, 1);
		}
		.tile:hover img {
			width: 200px !important;
			height: 200px !important;
			margin-top: -10px;
			margin-left: -10px;
			box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.9);
		}
		.tile:hover .year {
			display: block;
		}
		.tile:hover .photographer {
			display: block;
		}
		
		.original {
			position: fixed;
			bottom: 0px;
			z-index: 1100;
			box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.9);
		}
		
		</style>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
		<script>
		function hideOriginals() {
			$('.original').css({
				opacity: 0,
				bottom: '-500px'
			});
			// Make all the tiles show.
			$(".tile").css('opacity', 1.0);
		}
		function rotate($element, degree) {
			// For webkit browsers: e.g. Chrome
			$element.css({ WebkitTransform: 'rotate(' + degree + 'deg)'});
			// For Mozilla browser: e.g. Firefox
			$element.css({ '-moz-transform': 'rotate(' + degree + 'deg)'});
		}
		function startLoading($element) {
			$(".tile").not($element).css('opacity', 0.5);
		}
		function resetLoading($element) {
			// $(".tile").css('opacity', 1.0);
		}
		$(function() {
			//var hideOriginalsTimeout;
			var cols = Math.ceil($(window).width() / 180);
			var c = 0;
			var r = 0;
			$(".tile > img").bind('load', function() {
				$this = $(this);
				if($this.get(0).width > 1 && $this.get(0).height > 1) {
					$this.css({
						width: 180,
						height: 180
					}).parent().css({
						left: c * 180,
						top: r * 180
					}).fadeIn();
					
					c++;
					if(c * 180 > $(window).width()) {
						c = 0;
						r++;
					}
				}
			}).parent().bind('mouseenter', function() {
				$(this).css('z-index', 1000);
			}).bind('mouseleave', function() {
				$(this).css('z-index', 1);
				hideOriginals();
			}).bind('click', function(e) {
				hideOriginals();
				//clearTimeout(hideOriginalsTimeout);
				
				$original = $("<div class='original'>").appendTo("body");
				var clickedLeft = e.clientX < $(window).width() / 2;
				if(clickedLeft) {
					$original.css('right', '-520px');
				} else {
					$original.css('left', '-520px');
				}
				
				var original = $("img", this).data('original');
				$image = $("<img>").appendTo($original).attr('src', original);
				startLoading($(this));
				$image.bind('load', { '$this': $(this), '$org': $original, 'clickedLeft': clickedLeft }, function(event) {
					resetLoading(event.data.$this);
					event.data.$org.css({
						WebkitTransition : 'all 1s ease-in-out',
						MozTransition    : 'all 1s ease-in-out',
						MsTransition     : 'all 1s ease-in-out',
						OTransition      : 'all 1s ease-in-out',
						transition       : 'all 1s ease-in-out'
					});
					if(event.data.clickedLeft) {
						event.data.$org.css('right', '0px');
					} else {
						event.data.$org.css('left', '0px');
					}
					var degrees = Math.random() * 20 - 10;
					rotate(event.data.$org, degrees);
					event.data.$org.bind('mouseleave', hideOriginals);
					//hideOriginalsTimeout = setTimeout(hideOriginals, 5000);
				});

				// TODO: Add a title ...
			});
			$('h1 #hack4dk').bind('click', function() {
				$(this).popover({
					placement: 'top'
				}).popover('show');
			});
		});
		</script>
	</head>

	<body>
		<h1><a href="http://hack4dk.wordpress.com/" target="_blank">#HACK4DK</a> - <a href="https://docs.google.com/presentation/d/1e77aApiTgVeWFSJJQp1f6lBk53mchY8LhLQ6pa1CWeM/view" target="_blank">Natmus Mosaic</a></h1>
		<form id="search">
			<input type="text" placeholder="Søg" name="q" autocomplete="off" value="<?php echo htmlspecialchars($searchterm, ENT_QUOTES, "UTF-8"); ?>">
		</form>
		<?php printImages(200) ?>
	</body>

</html>
