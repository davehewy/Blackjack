<?php

	// Because I feel strongly about not making a cock up of the layout.
	// Segregate it into views.
	
?>

<div class="main_holder">
	<div class="betting">
		<div class="betting_inner">
		
			<h1>Game Over</h1>
			
			<div class="ovfl">
				<div class="dealer_cards">
					<h4>Dealers Hand: <em>Total: <?=$dealer_cards_count?></em>	</h4>
					<ul class="cards">
					<?php
						foreach($dealer_cards as $k=>$v){
							echo '<li><img src="'.$card_theme.$v['type'].$v['suit'].'.'.$card_extension.'" class="'.$card_class.'"></li>';
						}
						
						if($finished!=1 || $bust==1){
							echo '<li><img src="'.$card_theme.'back.'.$card_extension.'" class="'.$card_class.'"></li>';
						}
						
					?>
					</ul>								
				</div>
			</div>
			
			<div class="game_over_text">
				<p><h3><?=$gameovertext?></h3></p>
			</div>
			
			<div class="your_hand clear">
			
				<h4>Your hand: <em>Total: <?=$cards_count?></em></h4>
				<ul class="cards">
				<?php				
					foreach($cards as $k=>$v){
						echo '<li><img src="'.$card_theme.$v['type'].$v['suit'].'.'.$card_extension.'" class="'.$card_class.'"></li>';
					}
				?>
				</ul>
				
			</div>
			
			<div class="action_controller">
					
				<form action="" method="post">
				<input type="betamt" name="betamt" value="<?=$bet?>" class="text">
				<button type="submit" name="playagain">Play Again</button>
					
				</form>
				
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
			<p><span class="large">$<?=number_format($ante)?></span></p>
			<?php if($game['double_down']>0){ echo '<br><em>Doubled down</em>'; }?>
			</p>
		</div>
	</div>	
	


	<div class="game_log">
		<div class="game_log_inner">
		
		<h4>Game Logs</h4>
			<?php
				if(count($logs)>0){
			?>
			<ul>
			<?php
				foreach($logs as $k=>$v):
					echo '<li><em>'.date("H:i:s",$v['time']).'</em>: '.$v['text'].'</li>';
				endforeach;
				
				}
				
			?>
			</ul>
		</div>
	</div>
</div>