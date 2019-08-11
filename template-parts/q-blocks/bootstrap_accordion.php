<?php
$acf_fieldgroup_name = 'content_accordion';
$adminExtraClass = false;

if(!empty($block['className'])) {
 	$adminExtraClass = ' ' . $block['className'];
 }
 if(!empty($block['align'])) {
 	$adminExtraClass ? $adminExtraClass .= 'text-'.$block['align'] : $adminExtraClass = 'text-'.$block['align'];
 }
//NOTE - the Align blocks functionality is translated to Bootstrap 4 align classes. Accordions are already dependent on BS to function but this specifically makes them BS4 dependent

if( have_rows($acf_fieldgroup_name) ): ?>

		<div class="panel-group panel-group-default<?php echo $adminExtraClass ? $adminExtraClass : ''?>">
		<?php
		while( have_rows($acf_fieldgroup_name)) : the_row();
			$this_title = get_sub_field('title');
			$this_id = str_replace(' ','_', strtolower( preg_replace("/[^A-Za-z ]/", "", $this_title) ) );
			?>
			<div data-toggle="collapse" data-target="#<?php echo $this_id ?>" class="panel panel-default">
				<h5 class="panel-heading"><?php echo $this_title ?></h5>
				<div id="<?php echo $this_id ?>" class="panel-collapse collapse">
		        	<div class="wysiwyg panel-body">
		        		<?php the_sub_field('panel_content'); ?>
		        	</div>
		        </div>
		    </div>
		<?php 
		endwhile;
	echo '</div>';
	endif;
?>