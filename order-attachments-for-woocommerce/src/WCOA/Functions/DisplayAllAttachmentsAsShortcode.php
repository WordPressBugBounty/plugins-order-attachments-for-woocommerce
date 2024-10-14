<?php

namespace DirectSoftware\WCOA\Functions;

use DirectSoftware\WCOA\Attachments\Attachment;
use DirectSoftware\WCOA\Common\Options;

/**
 * @author d.gubala
 */
final class DisplayAllAttachmentsAsShortcode
{
	public function __invoke(): string
	{
		$attachments = Attachment::get_all_by_user(get_current_user_id());
		$displayAttachmentName = Options::get('display_attachment_name') === true;

		if (empty($attachments))
		{
			return __("There are no attachments to display.", 'sld-wcoa');
		}

		$html =
			'<table class="woocommerce-orders-table shop_table shop_table_responsive account-orders-table">
				<thead>
					<tr>
						<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">' . __('Order', 'woocommerce') . '</th>
						<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date">' . __('Date') . '</th>';

				        if ($displayAttachmentName) {
		$html .=        '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-post-title">' . __('Name') . '</th>';
				        }
		$html .=
						'<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions">' . __('Actions') . '</th>
					</tr>
				</thead>
				<tbody>';
			    foreach ($attachments as $item ) {
				    $wcoa_order        = wc_get_order( $item['order_id'] );
				    $item['order_url'] = $wcoa_order->get_view_order_url();
		$html .=
					    '<tr>
			        <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number">
			            <a href="' . $item['order_url'] . '" >#' . $item['order_id'] . '</a>
			        </td>
					<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date">' . $item['post_date'] . '</td>';
				    if ( $displayAttachmentName ) {
		$html .=
						    '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-post-title">' . $item['post_title'] . '</td>';
				    }
		$html .=
					    '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
			            <a target="_blank" href="' . $item['guid'] . '" class="woocommerce-button wp-element-button button view">' . __('View') . '</a>
			        </td>
				</tr>';
			    }
		$html .=
				'</tbody>
			</table>';

		return $html;
	}
}