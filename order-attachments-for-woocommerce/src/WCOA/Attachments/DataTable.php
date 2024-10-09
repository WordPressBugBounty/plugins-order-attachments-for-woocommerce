<?php

namespace DirectSoftware\WCOA\Attachments;

use WC_Order;
use WP_List_Table;

/**
 * @author d.gubala
 */
class DataTable extends WP_List_Table
{
	public function get_columns(): array
	{
		return [
			'cb'            => '<input type="checkbox" />',
			'post_date'          => __('Date'),
			'post_title'         => __('Name'),
			'order_id'         => __('Order', 'woocommerce'),
			'post_author'          => __('Author'),
			'actions'   => __('Actions')
		];
	}

	private function get_table_data(): array
	{
		return Attachment::get_all();
	}

	public function prepare_items(): void
	{
		$table_data   = $this->get_table_data();

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();
		$primary  = 'name';
		$this->_column_headers = [$columns, $hidden, $sortable, $primary];

		usort( $table_data, [&$this, 'usort_reorder']);

		$per_page = 15;
		$current_page = $this->get_pagenum();
		$total_items  = count( $table_data );

		$table_data = array_slice( $table_data, ( ( $current_page - 1) * $per_page), $per_page);

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		]);

		$this->items = $table_data;
	}

	public function column_default($item, $column_name)
	{
		$order = wc_get_order($item['order_id']);

		$item['actions'] = self::actions($item['attachment_id'], $item['order_id']);

		$item['post_author'] = get_userdata( $item['post_author'] )->display_name;

		$item['post_title'] = '<a class="wcoa_nowrap" target="_blank" href="' . $item['guid'] . '">' . $item['post_title'] . '</a>';

		$item['order_id'] = $order instanceof WC_Order ? '<a href="' . $order->get_edit_order_url() . '">#' . $item['order_id'] .'</a>' : '<a>#' . $item['order_id'] .'</a>';

		switch ($column_name)
		{
			case 'post_date':
			case 'post_title':
			case 'order_id':
			case 'post_author':
			default:
				return $item[$column_name];
		}
	}

	public function column_cb($item)
	{
		return sprintf('<input type="checkbox" name="element[]" value="%s" />', $item['attachment_id'] );
	}

	protected function get_sortable_columns(): array
	{
		return [
			'post_date'  => array('post_date', false),
			'post_title' => array('post_title', false),
			'order_id' => array('order_id', false),
			'post_author'  => array('post_date', false)
		];
	}

	public function usort_reorder($a, $b): int
	{

		$order_by = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'post_date';


		$order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';


		$result = strcmp($a[$order_by], $b[$order_by]);


		return ($order === 'asc') ? $result : -$result;
	}

	public static function actions(int $attachment_id, int $order_id): string
	{
		$nonce_url = wp_nonce_url("admin-post.php?action=wcoa_delete&attachment_id=$attachment_id", 'wcoa_delete_' . $attachment_id);

		$content = '<div>';
		$content .= '<button id="wcoa-send-email-to-customer" wcoa-order-id="' . $order_id . '" wcoa-attachment-id="' . $attachment_id . '" type="button" class="button wcoa-btn wcoa-att-email"></button>';
		$content .= ' ';
		$content .= '<a href="' . $nonce_url . '" title="' . __('Remove') . '" id="wcoa-delete-attachment" wcoa-order-id="' . $order_id . '" wcoa-attachment-id="' . $attachment_id . '" role="button" class="button wcoa-btn wcoa-att-trash"></a>';
		$content .= '</div>';
		return $content;
	}

}