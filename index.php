	<?php

	// =========== 
	// ! This is our main demonstration file.  
	// =========== 
	
	define('CONFIG', FALSE);
	define('DEV_ENV' , TRUE); // Set to true to turn into devolopment environment
	define('EXT', '.php');
	define('DS', DIRECTORY_SEPARATOR);
	define('ROOT', dirname(dirname(__FILE__)).'/blackjack/');
	define('DEFAULT_CONFIG','config/config'.EXT);
		
	// =========== 
	// ! Storage? Required to keep the game state and cards, constant.
	// ===========
	
	define('DEFAULT_STORAGE','database');
	define('BLACKJACK_TABLE','blackjack');
	define('USER_TABLE','users');
	
	// =========== 
	// ! First of all we will load in any config files we need namely config/config.php override using the above variable.
	// =========== 
	
	if(CONFIG){
		if(file_exists(ROOT.CONFIG))
			require_once(ROOT.CONFIG);
	}else{
		require_once(ROOT.DEFAULT_CONFIG);
	}
		
	// =========== 
	// ! Lets make a small function to retrieve items from our config elsewhere in our blackjack app.  
	// =========== 
	
	function get_config_item($var,$var_deux=false){
		global $config;
		if($var_deux)
			return isset($config[$var][$deux]) ? $config[$var][$var_deux] : false;
		else
			return ($config[$var]) ? $config[$var] : false;
	}
	
	// =========== 
	// ! Now we need to load in our core functions file.  
	// =========== 
	
	require_once(ROOT.'core'.EXT);
		
	// =========== 
	// ! Connect our database if we are using that as storage using data stored in our config file
	//   Remember to keep it safe!!!
	// =========== 	
	
	if(DEV_ENV)
		$connection = $config['database']['dev'];
	else
		$connection = $config['database']['live'];
	
		
	if(DEFAULT_STORAGE=='database')
		connect_db($connection);
		
	// =========== 
	// ! Now we've got a connection lets create a reference to the database class.  
	// =========== 
	
	$DB = new Db;
	
	// =========== 
	// ! Now the database is connected, lets include our blackjack functions class  
	// =========== 
	
	require_once(ROOT.'blackjack'.EXT);
	
	// =========== 
	// ! Now importantly, lets find how your storing the current players session id.  
	// =========== 
	
	$playerid = 1;
	
	// =========== 
	// ! Lets instantiate that class to get it ready for some bits and bobs.  
	// =========== 
	
	try {
	    
	    $blackjack = new blackjack($playerid,$DB);
		
		// =========== 
		// ! Quite cunningly we will include post requests at this point.  
		// =========== 
		
		if(!empty($_POST)){
			
			// Something is being posted.
			foreach($_POST as $k=>$v):
				if(method_exists($blackjack,$k)){
					$blackjack->$k();
					break;
				}
			endforeach;
			
		}
		
		// =========== 
		// ! If you wish to include a header file here you can, by default it will include the system header file.  
		// =========== 
		
		include_once(ROOT.'header'.EXT);
	    
	    // =========== 
	    // ! And now we are ready to rock and roll. 
	    //   Our next step is to fetch and return the state of the current game
	    //   or if no game exists then show a start screen.  
	    // =========== 
	    
	    $blackjack->getState();
	    
	} catch (Exception $e) {
					
		// =========== 
		// ! Catch errors from our class here.  
		// =========== 
	
	    $error = 'Caught exception: '.  $e->getMessage(). "\n";
	}	
	
	// =========== 
	// ! Finally include the template footer  
	// =========== 
	
	include_once(ROOT.'footer'.EXT);
	
		

	
	
	
	
	
	
