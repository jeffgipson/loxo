<form method="post" action="">
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="license_key"><?php _e( 'License Key', 'loxo' ); ?></label></th>
				<td>
					<input name="license_key" type="text" id="license_key" value="" class="regular-text">
					<p class="submit">
						<button type="submit" name="update" class="button button-primary"><?php _e( 'Update License', 'loxo' ); ?></button>
						<button type="submit" name="deactivate" class="button button-secondary"><?php _e( 'Deactivate License', 'loxo' ); ?></button>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</form>
