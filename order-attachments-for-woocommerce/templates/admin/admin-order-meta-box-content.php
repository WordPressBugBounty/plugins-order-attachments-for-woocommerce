<?php

use DirectSoftware\WCOA\Attachments\Attachment;

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

$current_order_id = isset($_GET['page'], $_GET['id']) && !get_the_ID() && $_GET['page'] === 'wc-orders' ? (int)$_GET['id'] : get_the_ID();

$attachments = Attachment::get_all_by_order($current_order_id);

$wcoa_visibility_class = empty($attachments) ? 'wcoa-content-hide' : 'wcoa-content-show';

?>
	<p>
		<input type="file" id="wcoa-attachment" value=""/>
	</p>
	<p>
		<button type="button" id="wcoa-send-btn" class="button" disabled><?php esc_html_e('Add','woocommerce'); ?></button>
	</p>
	<div id="wcoa-response-area"></div>
    <div class="<?php print $wcoa_visibility_class; ?>" id="wcoa-all-attachments-content">
        <hr />
        <h4><?php _e('Downloadable files','woocommerce'); ?>:</h4>
        <ul id="wcoa-order-attachments-list">
			<?php foreach ($attachments as $item)
            { ?>
                <li class="wcoa-new-item">
                    <a title="<?php _e('Attachment Preview'); ?>" data-text-copied="<?php _e('Copied'); ?>" target="_blank" href="<?php print $item['guid']; ?>"><?php print $item['post_title']; ?></a>
                    <div class="copy-to-clipboard"></div>
                </li> <?php
			} ?>
        </ul>
    </div>
<?php
