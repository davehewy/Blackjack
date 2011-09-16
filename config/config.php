<?php
/*

	Blackjacks Main Config file v1.0
	

*/

	$config = array();
	
	$config['database']['dev'] = array(
		"user" => "root",
		"password" => "root",
		"database" => "blackjack",
		"host" => "localhost",
		"char_set" => "utf8",
		"dbcollat" => "utf8_general_ci"
	);
	
	$config['card_theme'] = "/blackjack/assets".DS."images".DS."modern_deck/";
	
	$config['card_image_extension'] = 'png';
	
	$config['card_class'] = "bj_card";
	
	$config['base_url'] = "http://localhost:8888/blackjack/";