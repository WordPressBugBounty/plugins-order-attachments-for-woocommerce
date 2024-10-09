<?php

use DirectSoftware\WCOA\Attachments\Attachment;
use DirectSoftware\WCOA\Common\Options;

$wcoa_attachments = Attachment::get_all_by_user(get_current_user_id());
$wcoa_option_display_attachment_name = Options::get('display_attachment_name') === true;

if (empty($wcoa_attachments))
{
    _e("There are no attachments to display.", 'sld-wcoa');
    return;
}

?>
<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
	<thead>
	<tr>
		<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php _e('Order', 'woocommerce'); ?></th>
		<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><?php _e('Date'); ?></th>
        <?php if ($wcoa_option_display_attachment_name) { ?>
            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-post-title"><?php _e('Name'); ?></th>
        <?php } ?>
		<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><?php _e('Actions'); ?></th>
	</tr>
	</thead>
	<tbody>
    <?php foreach ($wcoa_attachments as $item )
    {
        $wcoa_order = wc_get_order($item['order_id']);
        $item['order_url'] = $wcoa_order->get_view_order_url();
    ?>
	<tr>
        <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number">
            <a href="<?php print $item['order_url']; ?>" >#<?php print $item['order_id']; ?></a>
        </td>
		<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date"><?php print $item['post_date']; ?></td>
		<?php if ($wcoa_option_display_attachment_name) { ?>
            <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-post-title"><?php print $item['post_title']; ?></td>
		<?php } ?>
		<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
            <a target="_blank" href="<?php print $item['guid']; ?>" class="woocommerce-button wp-element-button button view"><?php _e('View'); ?></a>
        </td>
	</tr>
    <?php } ?>
	</tbody>
</table>
