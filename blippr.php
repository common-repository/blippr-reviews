<?php

/*
	Plugin Name: blippr
	Plugin URI: http://blog.andreaolivato.net/software/blippr-wordpress-plugin
	Description: Mashable like Blippr plugin to let user review anything directly from your blog.
	Version: 0.1
	Author: Andrea Olivato
	Author URI: http://blog.andreaolivato.net/
	
    Copyright (C) 2009  Andrea Olivato <personal@andreaolivato.net>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!function_exists('json_decode')) {
    include('JSON.php');
	function json_decode($data) {
        $json = new Services_JSON();
        return( $json->decode($data) );
    }
}

function blippr_scripts() {
	?>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" /></script>
	<script type="text/javascript">
	<!--
		blippr = {root: "http://www.blippr.com", apiroot: "http://api.blippr.com/v2", sourceKey: "" };
	//-->		
	</script>
	<script type="text/javascript" src="http://www.blippr.com/javascripts/title_widget.js"></script>
	<? 
}

function blippr_button() {	
	?>
	<script type="text/javascript">
	<!--
		jQuery(document).ready(function($) {
			
			var bar = $("#ed_toolbar")[0];
			var blippr_tag = function() {
				edInsertContent(edCanvas, content);
			}

			if (bar) {
			  var blipprtag = document.createElement('input');
			  blipprtag.type = 'button';
			  blipprtag.value = 'blippr';
			  blipprtag.className = 'ed_button';
			  blipprtag.title = 'blippr';
			  blipprtag.id = 'ed_blippr';
			  bar.appendChild(blipprtag);
			  edButtons[edButtons.length] = new edButton("ed_blippr", "blippr", "[blippr]", "[/blippr]");
			  blipprtag.numb = edButtons.length - 1;
			  blipprtag.onclick = function() {
				edInsertTag(edCanvas, this.numb);
			  }
			}
		});
	//-->		
	</script>
	<?
}
	
function blippr_shortcode($attrs, $content) {
	
	$rem = $content;
	$url = 'http://api.blippr.com/v2/titles/autocomplete.json?q='.$content;
	$filename= 'wp-content/cache/blippr/'.md5("Blippr".$url);
	
	if( !file_exists('wp-content/cache/blippr/') || !is_dir('wp-content/cache/blippr/') )
		@mkdir('wp-content/cache/blippr/',0777,true);

	if (file_exists($filename)) {
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$split = explode("|",$contents);
		$id = $split[0];
		$content = $split[1];
		$color = $split[2];
		$range = $split[3];
	} else {
		$ch = curl_init ($url) ;
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
		$res = curl_exec ($ch) ;
		curl_close ($ch) ;

		$obj = json_decode($res);
		
		if ($obj[0]=='')
			return $rem;

		$content = $obj[0]->data->title;
		$id = $obj[0]->data->id;

		$url = 'http://api.blippr.com/v2/titles/'.$id.'.xml';
		$ch = curl_init ($url) ;
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
		$res = curl_exec ($ch) ;
		curl_close ($ch) ;
		
		if( !$res || $res == 'Requested resource was not found.')
			return $rem;
			
		$match = preg_match_all('/score="[0-9]*/',$res,$matches);
		$score=str_replace('score="',"",$matches[0][0]);

		switch ($score) {
			case ($score>70) :
				$range="07";
				$color='#A5EDFF';
				break;
			case ($score>50) :
				$range="05";
				$color='#D3FF63';
				break;
			case ($score>30) :
				$range="03";
				$color='#FFE586';
				break;
			default:
				$range="01";
				$color='#FF94A8';
		}

		$fp = @fopen($filename, 'w+');
		@fwrite($fp, $id."|".$content."|".$color."|".$range);
		fclose($fp);
	}
	$text = '<span class="blippr-nobr" style="color:'.$color.'">'.$content.'<span class="blippr-nobr"><a href="http://www.blippr.com/apps/'.$id.'-'.$content.'" target="_blank" rel="http://www.blippr.com/apps/'.$id.'-'.$content.'.whtml" class="blippr-inline-smiley blippr-inline-smiley-'.$range.'"><span>'.$content.'</span><img class="wp-smiley" src="http://static1.blippr.com/images/inline-face_'.$range.'.png?'.time().'" alt="'.$content.'" style="padding:0 !important;margin-right:5px !important;margin-left:5px !important;" /></a></span></span>';
	return $text;
}

add_action('admin_head','blippr_button',999);
add_action('wp_head', 'blippr_scripts', 999);
add_shortcode('blippr', 'blippr_shortcode');

?>
