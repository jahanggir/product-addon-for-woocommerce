<p class="helium-adding" style="clear:both; padding-top: .5em;">
	<label for="helium_add">
		<input type="checkbox" id="helium_add" name="helium_add" value="yes" <?php checked($current_value, 1, false); ?>>
		<?php
		// echo str_replace('{price}', $price_text, wp_kses_post($product_helium_addon_message));
		echo str_replace('{price}', $price_text, $product_helium_addon_message);
		?>
	</label>
</p>