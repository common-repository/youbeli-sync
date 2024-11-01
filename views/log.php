<div class="wrap youbeli_wrap">
	<h2>Log</h2>
	<?php if ( $deleted ) { ?>
	<div class="notice notice-success is-dismissible">
		<p>Delete Successfully!</p>
	</div>
	<?php } ?>
	<p>File size: <?php echo $size . ' ' . $unit ;?></p>
	<a class="button-primary" target="_blank" href="<?php echo esc_url( Youbeli::get_page_url( 'download_log' ) ); ?>">Download log</a>
	<a class="button-primary button-danger" href="<?php echo esc_url( Youbeli::get_page_url( 'clear_log' ) ); ?>">Clear log</a>
</div>

