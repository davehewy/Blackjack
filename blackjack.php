<?php
/**
 * Blackjack Byte
 *
 * A paid blackjack script
 *
 * @package		Blackjack Byte
 * @author		David Heward
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

	class Blackjack{
		
		// Blackjack setups
		
		var $suits = array("d","h","s","c");
		var $specials = array(1=>"A",11=>"J",12=>"Q",13=>"K");
		
		// Regular setups
	
		var $id = null;
		var $db = null;
		var $user = null;
		var $config = array();
		
		// Variable to use for finished text.
		var $finish_text;
		
		// User got insurance?
		private $has_insurance = false;
		private $has_doubled = false;
		
		
		/**
		*
		* On construct; Must be passed a player & the database class. 
		*/
		
		function __construct($playerid='',$db){
		
			global $config;
			$this->config = $config;
		
			if($playerid):
			
				$this->id = $playerid;
				$this->db = $db;
			
			else:
			
				 throw new Exception('Class cannot be instantiated without a playerid.');
			
			endif;
			
		}
		
		
		/**
		*
		* Get the state of the current game to the view. 
		*/
		
		function getstate(){
			
			// First check to see if there is a game.
			
			if($game = $this->_gameRow()):
			
				$this->user = $this->_getUser();
						
				if($game['finished']==1):
				
					// Fetch page info
					
					// Fetch the card theme.
					
					$game['card_theme'] = get_config_item('card_theme');
					$game['card_extension'] = get_config_item('card_image_extension');
					$game['card_class'] = get_config_item('card_class');					
					
					// Cards
					
					$game['cards'] = json_decode($game['cards'],TRUE); 
					$game['dealer_cards'] = json_decode($game['dealer_cards'],TRUE); 
					
					// Count them
					
					$game['cards_count'] = $this->_countCards($game['cards']);
					$game['dealer_cards_count'] = $this->_countCards($game['dealer_cards']);
				
					// Fetch the final game logs.
					
					$game['logs'] = $this->_fetchLogs($game['id']);
					
					// Fetch some finished text.
					
					$game['_gameOvertext'] = $this->_getFinishText();
				
					// Now delete the row along with its logs.
					
					$this->_deleteGame($game['id']);
					
					// Include user information
					
					$game['user'] = $this->user;
					
					// Load up the finished screen
					
					$this->_loadScreen('game_over',$game);
					
				else:
				
					// Load up the main screen
					
					$this->_mainScreen();
				
				endif;
			
			else:
				
				// No game row display the bet screen.
				
				$this->_loadScreen('place_bet');
				
			endif;
			
		}
		
		/**
		*
		* Deal with the submittal of requests from the page.
		*/
		
		/**
		*
		* Starting with playagain. 
		*/
		
		function playagain(){
			
			$this->user = $this->_getUser();
			$bet = trim($_POST['betamt']);
			if(is_numeric($bet)){
			
				if($this->user['money']>=$bet){
				
					// Remove the cash from the user.
					$this->_updateUser("money=money-'$bet'");
			
					// We now must start the game with and hand some cards out.
					
					$player_cards = $this->_getCards(2);
					$dealer_cards = $this->_getCards(1);
					
					// Store the cards in a JSON array for convenience.
					
					$player_cards_store = json_encode($player_cards);
					$dealer_cards_store = json_encode($dealer_cards);
					
					// Store the count
					
					$player_count = $this->_countCards($player_cards);				
					
					// Insert game.
					
					$gameid = $this->db->insert(BLACKJACK_TABLE,array(
						"playerid" => $this->id,
						"cards" => $player_cards_store,
						"dealer_cards" => $dealer_cards_store,
						"time_started" => time(),
						"bet"  => $bet,
						"ante" => $bet
					),true);
					
					// Insert a log for the game start.
					
					$this->_makeLog(array(
						"gameid" => $gameid,
						"playerid" => $this->id,
						"text" => sprintf(gettext("Id #%s: Plays again with a $%s ante."),$this->user['id'],number_format($bet))
					));
					
					// Test for blackjack on first set of hands.
									
					if($this->_blackjackHand($player_count)){
						
						// The player has blackjack.
						
						$game = $this->_gameRow();
						
						$dealer_hand = $this->_finishDealerHand($game,true);
						
						$dealer_count = $this->_countCards($dealer_hand);
						
						$this->_gameOutcome($player_count,$dealer_count,$game);
						
						$this->_gameOver($game);
						
					}	
				
				}else{
				
					throw new Exception("You do not have this much cash");
				
				}			
				
			}else{
			
				throw new Exception("Bet must be numeric");
			
			}
		}
		
		
		/**
		*
		* Submit a new bet to begin playing. 
		*/
		
		function submitbet(){
		
			$this->user = $this->_getUser();
			$bet = trim($_POST['ante']);
			if(is_numeric($bet)){
				if($this->user['money']>=$bet){
					
					// Remove the cash from the user.
					$this->_updateUser("money=money-'$bet'");
					
					// We now must start the game with and hand some cards out.
					
					$player_cards = $this->_getCards(2);
					$dealer_cards = $this->_getCards(1);
					
					// Store the cards in a JSON array for convenience.
					
					$player_cards_store = json_encode($player_cards);
					$dealer_cards_store = json_encode($dealer_cards);
					
					// Store the count
					
					$player_count = $this->_countCards($player_cards);

					// Insert game
					
					$gameid = $this->db->insert(BLACKJACK_TABLE,array(
						"playerid" => $this->id,
						"cards" => $player_cards_store,
						"dealer_cards" => $dealer_cards_store,
						"time_started" => time(),
						"bet"  => $bet,
						"ante" => $bet
					),true);
					
					// Insert a log for the game start.
				
					$this->_makeLog(array(
						"gameid" => $gameid,
						"playerid" => $this->id,
						"text" => sprintf(gettext("ID #%s: Starts a new blackjack game with a $%s ante."),$this->user['id'],number_format($bet))
					));
					
					// Insert a log for the first cards drawn.
					
					$this->_makeLog(array(
						"gameid" => $gameid,
						"playerid" => $this->id,
						"text" => sprintf(gettext("Dealer deals ID #%s: a %s of %s & a %s of %s (total %s)."),$this->user['id'],$this->_returnCardName($player_cards[0]['type']),$this->_returnSuit($player_cards[0]['suit']),$this->_returnCardName($player_cards[1]['type']),$this->_returnSuit($player_cards[1]['suit']),$player_count)
					));					
					
					// Test for blackjack on first set of hands.
									
					if($this->_blackjackHand($player_count)){
						
						// The player has blackjack.
						
						$game = $this->_gameRow();
						
						$dealer_hand = $this->_finishDealerHand($game,true);
						
						$dealer_count = $this->_countCards($dealer_hand);
						
						$this->_gameOutcome($player_count,$dealer_count,$game);
						
						$this->_gameOver($game);
						
					}							
					
			}else{
				throw new Exception("You do not have this much cash");
			}
			
			}
			
		}
		
		
		/**
		*
		* Hit function: Deals a new card for the player and tests for bust 
		*/
		
		function hit(){
			
			if(isset($_POST['hit'])){
			
				$this->user = $this->_getUser();
				if($game = $this->_gameRow()){
						
					$cards = json_decode($game['cards'],TRUE);
					$card_drawn = $this->_getCards(1,true);
					$cards[] = $card_drawn;
					
					// Count the new array of cards. See if they went bust.
					
					$new_count = $this->_countCards($cards);
					
					$put_back = json_encode($cards);
					$this->db->query("update ".BLACKJACK_TABLE." set cards='$put_back',round=round+'1' where id='{$game['id']}'");					
					
					if($new_count<22){
					
						$this->_makeLog(array(
							"gameid" => $game['id'],
							"playerid" => $this->id,
							"text" => sprintf(gettext("Id #%s hits and gets a %s of %s (total %s)"),$this->id,$this->_returnCardName($card_drawn['type']),$this->_returnSuit($card_drawn['suit']),$new_count)
						));
					
					}else{
						
						// Player is bust, send them to our bust function.
						
						$this->_isBust($new_count,$game);
						
						// Log it.
						
						$this->_makeLog(array(
							"gameid" => $game['id'],
							"playerid" => $this->id,
							"text" => sprintf(gettext("Id #%s hits and gets a %s of %s (total %s). BUST."),$this->id,$this->_returnCardName($card_drawn['type']),$this->_returnSuit($card_drawn['suit']),$new_count)
						));						
						
					
					}
					
				
				}
				
			}
			
		}
		
		
		/**
		*
		* Double down; Player doubles down if they can afford the ante. 
		*/
		
		function doubledown(){
			
			if(isset($_POST['doubledown'])){
			
				$this->user = $this->_getUser();
				if($game = $this->_gameRow()){
					
					if($game['double_down']==0){
					
						if($this->user['money']>=$game['ante']){
							
							// Set a variable.
							$this->has_doubled = true;
							
							// Remove the cash from the user.
							$this->_updateUser("money=money-'{$game['ante']}'");
													
							$cards = json_decode($game['cards'],TRUE);
							$card_drawn = $this->_getCards(1,true);
							$cards[] = $card_drawn;
							
							$new_count = $this->_countCards($cards);
							$put_back = json_encode($cards);
																	
							// Set new ante.
							$ante = $game['ante']*2;
							
							$this->db->query("update ".BLACKJACK_TABLE." set cards='$put_back',double_down='1',ante='$ante' where id='{$game['id']}'");
							
							$this->_makeLog(array(
								"gameid" => $game['id'],
								"playerid" => $this->id,
								"text" => sprintf(gettext("Id #%s doubles down, increasing the ante to $%s and draws a %s of %s (total %s)."),$this->id,number_format($ante),$this->_returnCardName($card_drawn['type']),$this->_returnSuit($card_drawn['suit']),$new_count)
							));	
							
							// Due to the traditional rule of double down, you may draw only one card.
							// Thus the game must be finished after this.
							
							$game = $this->_gameRow();
							
							// Dealers cards
							
							$dealers_cards = $this->_finishDealerHand($game,false);
							
							// Dealer cards drawn
												
							$dealers_count = $this->_countCards($dealers_cards);
							
							// Write the new dealers cards into the game array.
							
							$game['dealer_cards'] = json_encode($dealers_cards);
							
							$this->_gameOutcome($new_count,$dealers_count,$game);
							
							// And mark it as game over very quickly.
							
							$this->_gameOver($game);
						
						}else{
							throw new Exception("You do not have enough money to double.");
						}
					
					}else{
						throw new Exception("You may only double once.");
					}
				
				}
			
			}
			
		}
		
		
		/**
		*
		* Stand; Player stands and game outcome is decided. 
		*/
		
		function stand(){
		
			if(isset($_POST['stand'])){
				
				$this->user = $this->_getUser();
				if($game = $this->_gameRow()){				
				
					// Your cards
					$cards = json_decode($game['cards'],TRUE);
					$your_count = $this->_countCards($cards);
					
					// Dealers cards
					
					$dealers_cards = $this->_finishDealerHand($game);
					
					// Dealer cards drawn
										
					$dealers_count = $this->_countCards($dealers_cards);
					
					// Write the new dealers cards into the game array.
					
					$game['dealer_cards'] = json_encode($dealers_cards);
					
					$this->_gameOutcome($your_count,$dealers_count,$game);
					
					// And mark it as game over very quickly.
					
					$this->_gameOver($game);
					
				}
			
			}
		
		}
		
		
		/**
		*
		* Insurance; User takes out insurance for the current game, if they can afford ante/2 
		*/
		
		function insurance(){
					
			if(isset($_POST['insurance'])){
			
				$this->user = $this->_getUser();
				if($game = $this->_gameRow()){
				
					if($game['insurance']==0){
					
						$dealer_cards = json_decode($game['dealer_cards'],TRUE);
						$dealers_count = $this->_countCards($dealer_cards);
						if($dealers_count==11){
							
							$player_cards = json_decode($game['cards'],TRUE);
							if($this->_cardCount($player_cards)==2){
								
								$insurance_cost = floor($game['ante']/2);
								if($this->user['money']>=$insurance_cost){
								
									$this->_makeLog(array(
										"gameid" => $game['id'],
										"playerid" => $this->id,
										"text" => sprintf(gettext("Id #%s takes out insurance of $%s paying 2:1 if the dealer draws blackjack."),$this->id,number_format($insurance_cost))
									));									
									
									$this->_updateUser("money=money-'$insurance_cost'");
									$this->_updateGame($game['id'],"insurance='$insurance_cost'");
									
								}
													
							}
							
						}
					
					}
				
				}				
			
			}
			
		}
		
		
		/**
		*
		* Player can optionally choose to end the game if he likes; 
		*/
		
		function end_game(){
		
			if(isset($_POST['end_game'])):
			
				$this->user = $this->_getUser();
				
				if($game = $this->_gameRow()):
					$this->_deleteGame($game['id']);
				endif;
				
			endif;
			
		}
		
		/**
		*
		* Start utilities, used throughout the functionality of the script.
		*/
		
		/**
		*
		* check if the player is bust or not.
		* @_isBust 
		*/
		
		function _isBust($player_count,$game){
		
			/* Player is bust we must end the game direct back to the original view */
			
			$dealer = json_decode($game['dealer_cards'],TRUE);
			$dealer_count = $this->_countCards($dealer);
									
			$this->_gameOutcome($player_count,$dealer_count,$game);		
			$this->_markBust($game['id']);
			$this->_markFinished($game['id']);
			
		}
		
		
		/**
		*
		* if they game is finished, we process it here.
		* @_gameOver 
		*/
		
		function _gameOver($game){
		
			/* 	We can mark the game as finished for deletion now. */
			$this->_markFinished($game['id']);
			
		}
		
		/**
		*
		* decides the available actions to the user on the screen
		* @_loadActions 
		*/
		
		function _loadActions($game){
		
			$actions['hit'] = 1;
			$actions['double'] = 1;
			$actions['stand'] = 1;
			
			 if($game['dealer_cards_count']==11 && $game['insurance']<=0):
			 
			 	$actions['insurance'] = 1;
			 	
			 endif;
			
			return $actions;
		}
		
		
		/**
		*
		* returns the name of a special card
		* @_returnCardName 
		*/
		
		function _returnCardName($card){
		
			if(in_array($card,$this->specials)):
			
				switch($card):
					case "A": 
							$ret = "Ace";
							break;
					case "J": 
							$ret = "Jack";
							break;
					case "K":
							$ret = "King";
							break;
					case "Q": 
							$ret = "Queen";
							break;
				endswitch;
				
				return $ret;
				
			endif;
			
			return $card;
			
		}
		
		
		/**
		*
		* returns the name of the suit 
		* @_returnSuit 
		*/
		
		function _returnSuit($suit){
		
			switch($suit):
			
				case "h": 
						$ret = "Hearts";
						break;
				case "d": 
						$ret = "Diamonds";
						break;
				case "s":	
						$ret = "Spades";
						break;
				case "c":
						$ret = "Clubs";
						break;
						
			endswitch;
			
			return $ret;
			
		}
		
		
		/**
		*
		* count the current hand
		* move aces to be counted at the end regardless of their draw position
		* @_countCards 
		*/
		
		function _countCards($array){
		
			// first of all push all aces to the end of the card pack.
			$aces = array();			
			
			foreach($array as $k=>$v):
			
				if($v['type']=='A'):
				
					$aces[] = array("suit" => $v['suit'],"type"=>"A");
					unset($array[$k]);
					
				endif;
				
			endforeach;
			
			// then put them all back on the end.
			array_splice($array, count($array), 0, $aces);
			
			// finally create the returnable total		
			$tot = 0;
			if(count($array)>0):
			
				foreach($array as $k=>$v):
				
					if(is_numeric($v['type'])):
					
						$tot+=$v['type'];
					
					else:
					
						$tot+=$this->_fetchCardValue($tot,$v['type']);
					
					endif;
					
				endforeach;
			
			endif;
			
			return $tot;
		}
		
		
		/**
		*
		* Draw any number of new cards.
		* @_getCards 
		*/
	
		function _getCards($number,$single=false){
		
			$ret = array();
		
			if(!$single):
			
				for($i=0;$i<$number;$i++):
				
					$card = mt_rand(1,13);
					$suit = array_rand($this->suits,1);
					
					if(array_key_exists($card,$this->specials)):
					
						$card = $this->specials[$card];
					
					endif;
					
					$ret[] = array("suit"=>$this->suits[$suit],"type"=>$card);
				
				endfor;
				
			else:
			
				$card = mt_rand(1,13);
				$suit = array_rand($this->suits,1);
				
				if(array_key_exists($card,$this->specials)):
					
					$card = $this->specials[$card];
				
				endif;
				
				return array("suit"=>$this->suits[$suit],"type"=>$card);
			
			endif;

			return $ret;
		}
		
		
		
		/**
		*
		* Work out the outcome of a game.
		* @_gameOutcome 
		*/
		
		
		function _gameOutcome($your_count,$dealers_count,$game){
		
			// set some defaults at the top here.
			
			$win = 0;
			$draw = 0;
			$insurance = 0;
			$payout = $game['ante'];
		
			// has the player doubled?
			
			if($this->has_doubled):
				
				$payout = $game['ante']*2;
			
			endif;
			
			/* HANDLE GAME OUTCOMES. */

					
			/**
			*
			* player bust 
			*/
			
			if($your_count>21):
				
				$this->_setFinishText($game,5,1);
									
			/**
			*
			* Dealer has blackjack 
			*/
			
			elseif($this->_dealerBlackjack()):
				
				$this->_makeLog(array(
					"gameid" => $game['id'],
					"playerid" => $this->id,
					"text" => gettext("Dealer WINS. Dealer has BLACKJACK.")
				));
				
				// did they have insurance?
				
				if($this->_hasInsurance($game)):
				
					$insurance = true;
					$this->has_insurance = true;
					$payout = 0;
					$payout+= floor($game['insurance']*2);
					
				endif;
				
				// grab some finishing text
				
				$this->_setFinishText($game,3,2);				
			
					
			
			/**
			*
			* User has blackjack 
			*/
			
			elseif($this->_userBlackjack()):
			
					$ex = gettext(" Player has BLACKJACK.");
					$this->_setFinishText($game,4,3);
					$payout+=round($payout*1.5,0);
					
					// create a log
					
					$this->_makeLog(array(
						"gameid" => $game['id'],
						"playerid" => $this->id,
						"text" => sprintf(gettext("Player WINS.%s"),$ex)
					));
					
					// importantly mark as a win, to enable cash rebate.
					
					$win = true;				
					
			
			
			/**
			*
			* The game was a draw 
			*/
			
			elseif($this->_isDraw($your_count,$dealers_count)):
			
				if($your_count == 21 
				&& $this->_cardCount(json_decode($game['dealer_cards'],TRUE))==2 
				&& $this->_cardCount(json_decode($game['cards'],TRUE))==2):
				
					$ext = 'BLACKJACK';
					$this->_setFinishText($game,2,2);
					
				else:
				
					$ext = $your_count;
					$this->_setFinishText($game,2,1,$dealers_count);
					
				endif;
				
				// create a log.
			
				$this->_makeLog(array(
					"gameid" => $game['id'],
					"playerid" => $this->id,
					"text" => sprintf(gettext("DRAW. Dealer and Player both have %s."),$ext)
				));
				
				// importantly mark as a draw, to invoke cash rebate.
				
				$draw = true;
			
			
			/**
			*
			* Dealer wins with a high hand 
			*/
			
			elseif($this->_isHighHand($dealers_count,$your_count)):
				
				
				if($dealers_count==21 
				&& $this->_cardCount(json_decode($game['dealer_cards'],TRUE))==2):
				
					$ext = gettext(" Dealer has BLACKJACK.");
					$this->_setFinishText($game,3,2);
				
				else:
				
					$ext = sprintf(gettext(" Dealer hand of %s beats Players hand of %s."),$dealers_count,$your_count);
					$this->_setFinishText($game,3,1,$dealers_count,$your_count);
				
				endif;
			
				$this->_makeLog(array(
					"gameid" => $game['id'],
					"playerid" => $this->id,
					"text" => sprintf(gettext("Dealer WINS.%s"),$ext)
				));
			
				
			/**
			*
			* Player wins with a high hand 
			*/
			
			else:
				
				if($dealers_count>21):
				
					$ex = gettext(" Dealer is BUST.");
					$this->_setFinishText($game,4,2);
					$payout+=$payout;
				
				else:
				
					$ex = sprintf(gettext(" Player hand of %s beats Dealers hand of %s."),$your_count,$dealers_count);
					$this->_setFinishText($game,4,1,$dealers_count,$your_count);
					$payout+=$payout;
				
				endif;
				
				// make a log.
				
				$this->_makeLog(array(
					"gameid" => $game['id'],
					"playerid" => $this->id,
					"text" => sprintf(gettext("Player WINS.%s"),$ex)
				));
				
				
				// importantly mark as a win, to invoke cash rebate.
				
				$win = true;	
				
			endif;
			
			
			
			/**
			*
			* use the outcomes above to process, if any of them are true
			*/
			
			
			if($draw || $win || $insurance):
			
				$this->_handleOutcome($payout);
			
			endif;
		
		}
		
		
		/**
		*
		* simply hands the payout back to the user with a simple update.
		* @_handleOutcome 
		*/
		
		function _handleOutcome($payout){
		
			$this->_updateUser("money=money+'$payout'");
		
		}
		
		
		/**
		*
		* tests whethere or not the user has taken out insurance.
		* @_hasInsurance
		*/
		
		function _hasInsurance($game){
		
			if($game['insurance']>0):
			
				return true;
			
			endif;
			
			return false;
		}
		
		function _cardCount($cards){
			return count($cards);
		}
		
		
		/**
		*
		* hand1 is higher than hand2?
		* @_isHighHand 
		*/
			
		function _isHighHand($hand1,$hand2){
		
			if($hand1>$hand2 && $hand1<=21):
			
				return true;
			
			endif;
			
			return false;
		
		}
		
		
		/**
		*
		* are the hands a draw?
		* @_isDraw 
		*/
		
		function _isDraw($hand,$hand2){
		
			if($hand==$hand2):
			
				return true;
			
			endif;
			
			return false;
		
		}
		
		
		/**
		*
		* test for whether or not the user hand is blackjack.
		* @_userBlackjack 
		*/
		
		function _userBlackjack($game,$dealers_count,$your_count){
		
			if($your_count==21 
			&& $this->_cardCount(json_decode($game['cards'],TRUE))==2 
			&& ($this->_cardCount(json_decode($game['dealer_cards'],TRUE))>2 
			|| $dealers_count!=21)):
				
				return true;
				
			endif;
			
			return false;
			
		}
				
		
		/**
		*
		* test for whether or not the hand given is blackjack
		* @_dealerBlackjack 
		*/
		
		function _dealerBlackjack($game,$dealers_count,$your_count){
			if($dealers_count==21 
			&& $this->_cardCount(json_decode($game['dealer_cards'],TRUE))==2 
			&& $your_count==21 
			&& $this->_cardCount(json_decode($game['cards'],TRUE))>2):
			
				return true;
			
			endif;	
			
			return false;
				
		}
		
		
		/**
		*
		* finishes the dealer hand standing on a hard 17 
		* @_finishDealerHand
		*/
		
		function _finishDealerHand($game,$blackjack=false){
		
			$dealers_cards = json_decode($game['dealer_cards'],TRUE);
			
			
			/**
			*
			* dealer can only pull out one card as the user already has blackjack.
			*/
			
			if($blackjack 
			&& $this->_countCards($dealers_cards)==10 
			|| $this->_countCards($dealers_cards)==11):
			
				
				
				$card_drawn = $this->_getCards(1,true);
				$dealers_cards[] = $card_drawn;
				$new_count = $this->_countCards($dealers_cards);
				
				if($new_count>=17 && $new_count<=21):
				
					$extra = ' and STANDS';
				
				elseif($new_count>21):
				
					$extra = ' and BUSTS';
				
				endif;
				
				// make a log
				$this->_makeLog(array(
					"gameid" => $game['id'],
					"playerid" => $this->id,
					"text" => sprintf(gettext("Dealer draws a %s of %s (total %s) %s."),
					$this->_returnCardName($card_drawn['type']),
					$this->_returnSuit($card_drawn['suit']),
					$new_count,
					$extra)
				));		
							
			
			/**
			*
			* dealer can pull as many cards as neccessary whilst below a hard 17
			*/
				
			elseif(!$blackjack):
										
				do{
				
					$card_drawn = $this->_getCards(1,true);
					$dealers_cards[] = $card_drawn;
					$new_count = $this->_countCards($dealers_cards);
					
					if($new_count>=17 && $new_count<=21):
					
						$extra = ' and STANDS';
					
					elseif($new_count>21):
					
						$extra = ' and BUSTS';
					
					endif;
					
					
					// make a log
					
					$this->_makeLog(array(
						"gameid" => $game['id'],
						"playerid" => $this->id,
						"text" => sprintf(gettext("Dealer draws a %s of %s (total %s) %s."),$this->_returnCardName($card_drawn['type']),$this->_returnSuit($card_drawn['suit']),$new_count,$extra)
					));	
				
				} while($this->_countCards($dealers_cards)<17);
			
			endif;
			
			
			// update the database to reflect the dealers new card/cards.
			
			$new_dealer_cards = json_encode($dealers_cards);
			$this->db->query("update ".BLACKJACK_TABLE." set dealer_cards='$new_dealer_cards' where id='{$game['id']}'");			
			
			
			return $dealers_cards;
		
		}
		
		/**
		*
		* test to see if the hand tests true for count of 21
		* @_blackjackHand 
		*/
		
		function _blackjackHand($count){
		
			if($count==21):
				return true;
			endif;
			
			return false;
		
		}
		
		
		/**
		*
		* set some screen display text for the game outcome.
		* @_setFinishText
		* my apologies for the methodology used here.
		*/
		
		function _setFinishText($game,$type,$sub_type,$d_hand=false,$y_hand=false){
			
			switch($type):
				case 1:
						$this->finish_text = sprintf(gettext("You went bust and lost your ante of $%s."),
						number_format($game['ante']));
						break;
				case 2:
				
					switch($sub_type):
					
						case 1: 
						$this->finish_text = sprintf(gettext("You both have %s. It's a draw. You got your ante of $%s back."),
						$d_hand,
						number_format($game['ante'])
						);						
						break;
						
						
						case 2: 
						$this->finish_text = sprintf(gettext("You both have Blackjack, it's a draw. You got your ante of $%s back."),
						number_format($game['ante'])
						);					
						break;
						
					endswitch;
						
					break;
				
				case 3: 
				
					switch($sub_type):
						
						case 1: 
						$this->finish_text = sprintf(gettext("You lose! Dealer hand of %s beat's your hand of %s. You lose $%s."),
						$d_hand,
						$y_hand,
						number_format($game['ante'])
						);
						break;
							
						case 2: 
						if($this->has_insurance)
							$this->finish_text = sprintf(gettext("Dealer has Blackjack. You lose $%s. You had insurance and got back $%s + $%s."),
							number_format($game['insurance']),
							number_format($game['insurance'])
							); 
						else
							$this->finish_text = sprintf(gettext("Dealer has Blackjack. You lose $%s."),
							number_format($game['ante'])
							);
						break;
						
					endswitch;
					
					break;
						
				case 4:
				
					switch($sub_type):
					
						case 1: 
						if($this->has_doubled)
							$this->finish_text = sprintf(gettext("Your hand of %s beats Dealers hand of %s. You doubled and win $%s + $%s."),
							$y_hand,
							$d_hand,
							number_format($game['ante']),
							number_format($game['ante'])
							);	
						else
							$this->finish_text = sprintf(gettext("Your hand of %s beats Dealers hand of %s. You win $%s + $%s."),
							$y_hand,
							$d_hand,
							number_format($game['bet']),
							number_format($game['ante'])
							);																								
						break;
						
						case 2:	
						if($this->has_doubled)
							$this->finish_text = sprintf(gettext("Dealer is bust. You doubled and win $%s + $%s."),
							number_format($game['ante']),
							number_format($game['ante']*2)
							);							
						else
							$this->finish_text = sprintf(gettext("Dealer is bust. You win $%s + $%s."),
							number_format($game['ante']),
							number_format($game['ante'])
							);							
						break;
						
						case 3:							
						$blackjack = $game['ante']*1.5;
						$this->finish_text = sprintf(gettext("You have Blackjack. You win $%s + $%s."),
						number_format($game['ante']),
						number_format($blackjack)
						);
						break;
									
					endswitch;
					
					break;
					
				case 5: 
				
					switch($sub_type):
						case 1: 
						$this->finish_text = sprintf(gettext("You are bust. You lose $%s."),
						number_format($game['ante'])
						);
						break;
						
					endswitch;
					
					break;
					
			endswitch;
			
			return $txt;
			
		}
		
		function _getFinishText(){
			return ($this->finish_text) ? $this->finish_text : false;
		}
		
		function _markBust($id){
			$this->db->query("update ".BLACKJACK_TABLE." set bust='1' where id='$id'");
		}		
		
		function _markFinished($id){
			$this->db->query("update ".BLACKJACK_TABLE." set finished='1' where id='$id'");
		}
		
		function _deleteGame($id){
			$this->db->query("delete from ".BLACKJACK_TABLE." where id='$id'");
			$this->db->query("delete from game_logs where gameid='$id'");
		}
		
		function _fetchCardValue($tot,$v){
			switch($v){
				case ($v=="J" || $v=="K" || $v=="Q"): 
													$ret = 10;
													break;
				case "A": 
						if($tot+11>21)
							$ret=1;
						else
							$ret=11;
						break;
			}
			return $ret;
		}
		
		function _makeLog($vars){
			$vars['time'] = time();			
			$this->db->insert("game_logs",$vars);
		}
		
		function _fetchLogs($gameid){
			return $data['game']['logs'] = $this->db->query("select * from game_logs where gameid='{$gameid}' order by time ASC")->rows();
		}
		
		function _mainScreen(){
					
			$data['card_theme'] = get_config_item('card_theme');
			$data['card_extension'] = get_config_item('card_image_extension');
			$data['card_class'] = get_config_item('card_class');
			
			
			$data['game'] = $this->_gameRow();
			$data['game']['cards'] = json_decode($data['game']['cards'],TRUE); 
			$data['game']['dealer_cards'] = json_decode($data['game']['dealer_cards'],TRUE); 
			
			// Now we also need to populate the current count for cards.
			
			$data['game']['cards_count'] = $this->_countCards($data['game']['cards']);
			$data['game']['dealer_cards_count'] = $this->_countCards($data['game']['dealer_cards']);
			
			// Now lets determine the actions the user can take.
			
			$data['game']['actions'] = $this->_loadActions($data['game']);
			
			// Grab the games logs.
			$data['game']['logs'] = $this->_fetchLogs($data['game']['id']);
			
			// Add the user details to the screen.
			$data['user'] = $this->user;
									
			$this->_loadScreen('main_screen',$data);
		}
		
		function _loadScreen($var,$array=false){
			extract($array);
			include(ROOT.'views'.DS.$var.EXT);
		}
		
		function _getUser(){
			return $this->db->query("select * from ".USER_TABLE." where id='{$this->id}'")->as_assoc();
		}
		
		function _updateGame($id,$fields){
			if($fields){
				$this->db->query("update ".BLACKJACK_TABLE." set $fields where id='$id'");
			}
		}
		
		function _updateUser($fields){
			if($fields){
				$this->db->query("update ".USER_TABLE." set $fields where id='{$this->id}'");
			}
		}
		
		function _gameRow(){
			return $this->db->query("select * from ".BLACKJACK_TABLE." where playerid='{$this->id}'")->as_assoc();
		}
	
	}