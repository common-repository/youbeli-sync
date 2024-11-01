<div class="wrap youbeli_wrap">
	<form id="sync-form" action="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_category_setting' ) ); ?>" method="POST">
	<h1 class="wp-heading-inline">Category Setting</h1>
	<input type="hidden" name="do_action" value="get_youbeli_category">
	<button class="page-title-action" type="submit">Get Youbeli categories</button>
	<?php if ( $synced === true) { ?>
	<div class="notice notice-success is-dismissible">
		<p>Successfully sync Youbeli categories!</p>
	</div>
	<?php } elseif ( $synced === false) { ?>
	<div class="notice notice-error is-dismissible">
		<p>Failed to fetch Youbeli categories</p>
	</div>
	<?php } ?>
	</form>
	<p> Last Sync: <?php echo Youbeli::get_last_category_sync(); ?></p>
	
	<table class="widefat post fixed striped" cellspacing="0">
		<thead>
			<tr>
				<th scope="col" class="manage-column">WooCommerce Category</th>
				<th scope="col" class="manage-column">Youbeli Category</th>
				<th scope="col" class="manage-column">Action</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $categories as $category ) { ?>
			<tr>
				<td class="cat-name"><?php echo $category->name; ?></td>
				<td class="yb-cat-name"><?php echo $category->youbeli_name; ?></td>
				<td><a href="#inline" class="popup" data-woo-cat-id="<?php echo $category->term_id; ?>">Match</a></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php echo paginate_links( $pagination ); ?>
	<div id="inline" class="white-popup mfp-hide">
		<header><h3 id="select-title"></h3></header>
		<p id="cat-path"></p>
		<div id='cat-container'>
			<div class='item'>
				<ul>

					<?php foreach ( $youbeli as $category ) { ?>
					<li data-cat-id="<?php echo $category['id']; ?>" class="<?php echo ( ( $category['child'] > 0 ) ? 'parent' : '' ); ?>"><?php echo $category['name']; ?></li>
					<?php } ?>
				</ul>
			</div>
		</div>
		<button type="button" id="confirm" class="btn-btn-primary">Confirm</button>
	</div>

</div>
