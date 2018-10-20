<?php
/*
 * Set $header_cfg['autotab_isterm'] to TRUE if the control is used for TERM, leave out if used for DUR..
 * By setting on the 'autotab_isterm', you are ommiting the js zeropad function - autotab_starter_zeroz()
 */
?>


  <script type="text/javascript" src="../_js/autotab/jquery.autotab.js"></script>

  
  <script type="text/javascript">

		<?php foreach($header_cfg['autotab'] as $k => $v) :?>

			$(document).ready(function() {
				autotab_starter('<?=$v?>');
                <?php if (empty($header_cfg['autotab_isterm'][$k])) echo "autotab_starter_zeroz('".$v."');"?>
			});
			
		<?php endforeach;?>
	
		

		function autotab_starter(x) {
			
			$(x).autotab_magic().autotab_filter('numeric');
			$('#dur-hh, #dur-mm, #dur-ss').autotab_magic().autotab_filter('numeric');
		}


		function autotab_starter_zeroz(x) {
			
			$(x).mousedown(function() {if (this.value=='00') this.value='';});
			$(x).focusin(function() 	{if (this.value=='00') this.value='';});
			$(x).focusout(function() 	{this.value = zeropad(this.value);});
		}
		
		
		function zeropad(x) {

			var txt = (x) ? parseInt(x, 10) : 0;
			txt = ((txt < 10) ? '0' : '') + txt;
			
			return txt;
		}


	</script>




<?php

  /* 
  <script type="text/javascript">

	<?php
	foreach($js_autotab as $k => $v) :
	?>

		$(document).ready(function() {
			
			$('<?=$v?>').autotab_magic().autotab_filter('numeric');
			<?php if (@!$zeropad_off[$k]) :?>
			$('<?=$v?>').mousedown(function() {if (this.value=='00') this.value='';});
			$('<?=$v?>').focusin(function() {if (this.value=='00') this.value='';});
			$('<?=$v?>').focusout(function() {this.value = zeropad(this.value);});
			<?php endif;?>

		});
		
	<?php
	endforeach;
	unset($js_autotab);
	?>
		
		
	
	</script>
   */


