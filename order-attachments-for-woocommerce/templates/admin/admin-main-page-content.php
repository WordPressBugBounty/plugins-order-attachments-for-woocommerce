<?php

use DirectSoftware\WCOA\Attachments\DataTable;
use DirectSoftware\WCOA\Kernel;

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

// check user capabilities
if (!current_user_can('manage_options'))
{
    return;
}

if (isset($_COOKIE['wcoa_deleted']) && $_COOKIE['wcoa_deleted'] === 1)
{
    Kernel::notice_success_deleted();
    setcookie('wcoa_deleted', null, time() -1);
}

$default_tab = null;
$tab = $_GET['tab'] ?? $default_tab;

?>
    <div class="wrap">
        <nav class="nav-tab-wrapper">
            <a href="?page=wcoa" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>"><?php esc_html_e( 'Attachments', 'sld-wcoa' ); ?></a>
            <a href="?page=wcoa&tab=settings" class="nav-tab <?php if($tab==='settings'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e( 'Settings' ); ?></a>
        </nav>

        <div class="tab-content">
			<?php switch ($tab)
			{
				case 'settings':
					wcoa_admin_management_settings();
					break;
				default:
					wcoa_admin_management_main();
					break;
			} ?>
        </div>
    </div>
	<?php

function wcoa_admin_management_main(): void
{
	print '<form method="post">';

	$table = new DataTable();
	$table->prepare_items();
	$table->display();

	print '</form>';
}

function wcoa_admin_management_settings(): void
{ ?>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'wcoa_general' );
            do_settings_sections( 'wcoa_general_page' ); ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
        </form>
    <?php
}