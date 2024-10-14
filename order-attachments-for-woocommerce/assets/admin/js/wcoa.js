
const wcoaInputFileArea = document.getElementById('wcoa-attachment');
const wcoaAddAttachmentBtn = document.getElementById('wcoa-send-btn');

if (wcoaInputFileArea !== null && wcoaAddAttachmentBtn !== null) {
	wcoaInputFileArea.addEventListener('change', function() {
		if (wcoaInputFileArea.files !== 0)
		{
			wcoaAddAttachmentBtn.disabled = false;
		}
	});
}

function wcoaAssignAttachmentToList(data)
{
	const list = document.getElementById('wcoa-order-attachments-list');
	const listArea = document.getElementById('wcoa-all-attachments-content');
	const childList = list.querySelectorAll('li');

	let li = document.createElement('li');
	let a = document.createElement('a');

	a.innerHTML = data.title;
	a.href = data.url;

	li.appendChild(a);
	list.appendChild(li);

	if (childList.length === 0) {
		listArea.classList.remove('wcoa-content-hide');
		listArea.classList.add('wcoa-content-show');
	}
}

jQuery(document).ready(function($) {

	$('#wcoa-send-btn').click(function () {

		const responseArea = document.getElementById('wcoa-response-area');
		const requestParams = new FormData();

		requestParams.append('action', 'wcoa_add_attachment');
		requestParams.append('order_id', document.getElementById('post_ID').value );
		requestParams.append('attachment', wcoaInputFileArea.files[0]);
		requestParams.append('wcoa_add_attachment_nonce', wcoa_add_attachment_nonce);

		responseArea.innerHTML = '<div class="wcoa-loading"></div>';

		let btn = $(this);
		btn.attr("disabled", true);
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: requestParams,
			contentType: false,
			processData: false,
			success: function(response, status, result){

				if (response.code === 0)
				{
					wcoaAssignAttachmentToList(response.data);
				}

				responseArea.style.color = 'unset';
				responseArea.innerHTML = response.message;
				wcoaInputFileArea.value = null;
			},
			error: function (result, status){

				responseArea.style.color = 'red';
				responseArea.innerHTML = `Error ${result.status} - ${result.statusText}`;
				responseArea.innerHTML += result.responseText;
				wcoaInputFileArea.value = null;
			},
		});
	});

	function wcoaStateChange(btn) {
		btn.removeClass('wcoa-att-email');
		btn.addClass('wcoa-att-email-sent');
		setTimeout(function () {
			btn.removeClass('wcoa-att-email-sent');
			btn.addClass('wcoa-att-email');
		}, 2000);
	}


	$('.wcoa-att-email').click(function () {

		let currentBtn = $(this);

		const requestParams = new FormData();

		requestParams.append('action', 'wcoa_send_email_to_customer');
		requestParams.append('order_id', currentBtn.attr('wcoa-order-id') );
		requestParams.append('attachment_id', currentBtn.attr('wcoa-attachment-id') );

		currentBtn.removeClass('wcoa-att-email');
		currentBtn.removeClass('wcoa-email-success');
		currentBtn.removeClass('wcoa-att-email-error');

		currentBtn.addClass('wcoa-att-email-sent');

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: requestParams,
			contentType: false,
			processData: false,
			success: function(response, status, result) {

				if (response.code === 0)
				{
					currentBtn.removeClass('wcoa-att-email-sent');
					currentBtn.removeClass('wcoa-att-email-error');
					currentBtn.addClass('wcoa-att-email');
					currentBtn.addClass('wcoa-email-success');
				} else {
					currentBtn.removeClass('wcoa-att-email-sent');
					currentBtn.addClass('wcoa-att-email-error');
				}
			},
			error: function (result, status){

				currentBtn.removeClass('wcoa-att-email-sent');
				currentBtn.addClass('wcoa-att-email-error');
			},
		});

		//wcoaStateChange(currentBtn);

		//currentBtn.removeClass('wcoa-att-email');
		//currentBtn.addClass('wcoa-att-email-sent');


	});


});

const WCOA_attachmentList = document.getElementById('wcoa-order-attachments-list') ?? null;
if (WCOA_attachmentList !== null && WCOA_attachmentList.children.length !== 0) {
	for (const child of WCOA_attachmentList.children) {
		const link = child.querySelector('a[href]')
		const icon = child.querySelector('div[class="copy-to-clipboard"]');
		const copied = link.getAttribute('data-text-copied');
		if (link !== null && icon !== null && copied !== null) {
			icon.onclick = () => {
				navigator.clipboard.writeText(link.href);
				const previous = link.textContent;
				link.textContent = copied;
				setTimeout(() => link.textContent = previous, 1000);
			};
		}
	}
}