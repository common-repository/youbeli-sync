<div class="wrap youbeli_wrap">
	<h2>Setting</h2>
	<?php if ( $saved ) { ?>
	<div class="notice notice-success is-dismissible">
		<p>Successfully saved!</p>
	</div>
	<?php } ?>
	<form action="<?php echo esc_url( Youbeli::get_page_url( 'youbeli_setting' ) ); ?>" method="POST">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="youbeli_store_id">Youbeli Store Id</label>
					</th>
					<td class="forminp forminp-text">
						<input type="number" value="<?php echo $youbeli_store_id; ?>" id="youbeli_store_id" name="youbeli_store_id">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="youbeli_api_key">Youbeli API key</label>
					</th>
					<td>
						<input type="text" value="<?php echo $youbeli_api_key; ?>" id="youbeli_api_key" name="youbeli_api_key">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="youbeli_delivery_days">Delivery days</label>
					</th>
					<td>
						<input type="number" value="<?php echo $youbeli_delivery_days; ?>" id="youbeli_delivery_days" name="youbeli_delivery_days">
						<br>
						<span class="description">Default is 7 days</span>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit"><input type="submit" value="Save Changes" class="button-primary" name="Submit"></p>
	</form>
</div>

