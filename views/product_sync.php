<div class="wrap youbeli_wrap">
	<form id="sync-form" action="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_product_sync' ) ); ?>" method="POST">
		<h1 class="wp-heading-inline">Product Sync</h1>
		<a id="sync-all" data-action="sync_all" class="page-title-action">Sync All</a>
		<a id="sync-selected" data-action="sync_selected" class="page-title-action">Sync Selected</a>
		<a id="unsync-selected" data-action="unsync_selected" class="page-title-action">Unsync(Delete) Selected</a>
		<?php if ( isset( $sync_status ) && $sync_status ) { ?>
			<?php if ( $sync_status['sync_running'] ) { ?>
			<div class="notice notice-error is-dismissible">
				<p>Oops! Last sync was interrupted. Please <u id="sync-continue" data-action="<?php echo ( $sync_status['method'] == 'sync' ) ? 'sync_continue' : 'unsync_continue'; ?>" class="clickable">click me</u> to continue last sync.</p>
			</div>
			<?php } else { ?>
			<div class="notice notice-success is-dismissible">
				<p>Total product <?php echo $sync_status['method']; ?>: <?php echo $sync_status['total']; ?></p>
				<p>Success: <?php echo $sync_status['success_total']; ?>
				<p>Failed: <?php echo $sync_status['failed_total']; ?>
				<p>(<?php echo $sync_status['time_used']; ?>s)</p></p>
			</div>
			<?php } ?>
		<?php } ?>
		
		<input type="hidden" id="do_action" name="do_action">
		<p>Last Sync: <?php echo Youbeli::get_last_product_sync(); ?></p>
		<p>Note: Grouped product will not be synced</p>
		<ul class="subsubsub">
			<li class="all"><a href="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_product_sync' ) ); ?>" class="current" aria-current="page">All <span class="count">(<?php echo $total_products; ?>)</span></a> |</li>
			<li class="publish"><a href="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_product_sync', array( 'filter_synced' => 1 ) ) ); ?>">Synced <span class="count">(<?php echo $total_sync; ?>)</span></a> |</li>
			<li class="publish"><a href="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_product_sync', array( 'filter_synced' => 0 ) ) ); ?>">Unsync <span class="count">(<?php echo $total_unsync; ?>)</span></a> |</li>
			<li class="publish"><a href="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_product_sync', array( 'filter_error' => 1 ) ) ); ?>">Sync Error <span class="count">(<?php echo $total_error; ?>)</span></a></li>
		</ul>
		
		<table class="widefat post fixed striped" cellspacing="0">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
					<th scope="col" id="thumb" class="manage-column column-thumb">Image</th>
					<th scope="col" id="name" class="manage-column column-name column-primary">Name</th>
					<th scope="col" id="is_in_stock" class="manage-column column-is_in_stock">Stock</th>
					<th scope="col" id="price" class="manage-column column-price">Price</th>
					<th scope="col" id="type" class="manage-column column-type">Product type</th>
					<th scope="col" id="sync_error" class="manage-column column-error">Error</th>
					<th scope="col" id="sync_time" class="manage-column column-sync-time">Last sync</th>
					<th scope="col" id="status" class="manage-column column-status">Status</th>
				</tr>
			</thead>
			<tbody id="product-list">
				<?php foreach ( $products as $post ) { 
					if( version_compare( $woocommerce->version, '2.2', '<' ) ) { 
						$product = get_product($post); 
					} elseif ( version_compare( $woocommerce->version, '3.0', '<' ) ) { 
						$product = wc_get_product( $post ); 
					} else { 
						$product = $post;
					} ?>
				<tr>
					<th scope="row" class="check-column">
						<input id="cb-select-<?php echo $product->id; ?>" type="checkbox" name="check[]" value="<?php echo $product->id; ?>">
					</th>
					<td><?php echo '<a>' . $product->get_image( 'thumbnail' ) . '</a>'; ?></td>
					<td><?php echo '<strong><a class="row-title">' . esc_html( _draft_or_post_title( $product->id ) ) . '</a>'; ?></td>
						<td>
							<?php
							if ( $product->is_in_stock() ) {
								echo '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
							} else {
								echo '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
							}
							?>
						</td>
						<td><?php echo $product->get_price_html() ? $product->get_price_html() : '<span class="na">&ndash;</span>'; ?></td>
						<td><?php echo $product->product_type; ?></td>
						<td><?php Youbeli_Admin::get_sync_error_message( $product->id ); ?></td>
						<td><?php Youbeli_Admin::get_last_sync_time( $product->id ); ?></td>
						<td><?php Youbeli_Admin::get_sync_status_html( $product->id ); ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo $total; ?> items</span>
					<?php echo paginate_links( $pagination ); ?>
				</div>
			</div>
		</form>
	</div>
