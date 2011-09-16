<?php

	// Because I feel strongly about not making a cock up of the layout.
	// Segregate it into views.
	
?>

<div class="main_holder">
	<div class="betting">
		<div class="betting_inner">
			<?php
			if($game['insurance']>0){
				echo '<p><strong>';
				echo sprintf(gettext("Insurance taken: $%s <em>(pays 2:1 if dealer has Blackjack)</em>."),number_format($game['insurance']));
				echo '</strong></p>';
			}
			?>
			
			<div class="dealer_cards clear">
				<h4>Dealers Hand: <em> <?=$game['dealer_cards_count']?></em></h4>
				<ul class="cards">
				<?php
					foreach($game['dealer_cards'] as $k=>$v){
						echo '<li><img src="'.$card_theme.$v['type'].$v['suit'].'.'.$card_extension.'" class="'.$card_class.'"></li>';
					}
					
					if($game['finished']!=1){
						echo '<li><img src="'.$card_theme.'back.'.$card_extension.'" class="'.$card_class.'"></li>';
					}
					
				?>
				</ul>
			</div>
			<div class="your_hand clear">
			
				<h4>Your hand: <em><?=$game['cards_count']?></em></h4>
				<ul class="cards">
				<?php				
					foreach($game['cards'] as $k=>$v){
						echo '<li><img src="'.$card_theme.$v['type'].$v['suit'].'.'.$card_extension.'" class="'.$card_class.'"></li>';
					}
				?>
				</ul>
			</div>
			
			<div class="action_controller">
				
				<div>				<?php
				
					if(count($game['actions'])>0){ ?>
					
					<form action="" method="post">
					
					<?php
					
						foreach($game['actions'] as $k=>$v){
							switch($k){
								case "hit": 
											echo '<button type="submit" name="hit">Hit</button>';
											break;
								case "stand": 
											if($game['double_down']==0){
												echo '<button type="submit" name="doubledown">Double</button>';
											}
											break;
								case "double": 
											echo '<button type="submit" name="stand">Stand</button>';
											break;
								case "insurance":
											echo '<button type="submit" name="insurance">Insurance</button>';
											break;
							}
						}
						
					?>
					<button type="submit" name="end_game">End Game</button>
						
					</form>
				
				</div>

					
					<?php }
				
				?>
				
			</div>			

		</div>
	</div>

	<div class="game_log margbottom20">
		<div class="game_log_inner">
			<h4>Account</h4>
			<p>
			Your cash balance:
			<p><span class="large">$<?=number_format($user['money'])?></span></p>
			Ante: 
			<p><span class="large">$<?=number_format($game['ante'])?></span></p>
			<?php if($game['double_down']>0){ echo '<br><em>Doubled down</em>'; }?>
			</p>
		</div>
	</div>	

	<div class="game_log">
		<div class="game_log_inner">
		
		<h4>Game Logs</h4>
			<?php
				if(count($game['logs'])>0){
			?>
			<ul>
			<?php
				foreach($game['logs'] as $k=>$v):
					echo '<li><em>'.date("H:i:s",$v['time']).'</em>: '.$v['text'].'</li>';
				endforeach;
				
				}
				
			?>
			</ul>
		</div>
	</div>
</div>