<?php

use DirectSoftware\WCOA\Attachments\Attachment;
use DirectSoftware\WCOA\Attachments\RedirectionHandler;

if (!isset($_GET['key']) || $_GET['key'] === '')
{
	return RedirectionHandler::bad_request();
}

$wcoa_key_id = $_GET['key'];
$wcoa_attachment_id = 0;

if (isset($_GET['id']) && $_GET['id'] !== '')
{
	$wcoa_attachment_id = $_GET['id'];
}

$wcoa_attachment_url = Attachment::get_url($_GET['wcoa_attachment_for_order'], $wcoa_key_id, $wcoa_attachment_id);

if ($wcoa_attachment_url === false)
{
	return RedirectionHandler::bad_request();
}

RedirectionHandler::redirect($wcoa_attachment_url);

return true;