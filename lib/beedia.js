var $ = jQuery;
$(document).ready(function () {
	var requestURL = $('#request_url').val();
	// 批量上传
	var page = 1, limit = 5, total = 0, count = 1;
	var startBtn = true;
	var resync = false;
	$('.beedia-qiniu-sync').click(function () {
		$(this).attr({
			disabled: 'disabled'
		});
		get_media_list();
	});
	$('.beedia-qiniu-resync').click(function () {
		resync = true;
		$(this).attr({
			disabled: 'disabled'
		});
		get_media_list();
	});

	function get_media_list() {
		if (!startBtn) {
			return;
		}
		var metaQuery = null;
		if (!resync) {
			metaQuery = [
					{
						key: 'qiniu',
						compare: 'NOT EXISTS'
					}
				];
			page = 1;
		}
		$.ajax(requestURL, {
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'beedia_query_attachments',
				query: {
					paged: page,
					posts_per_page: limit,
					post_mime_type: 'image',
					post_status: 'inherit',
					post_type: 'attachment',
					meta_query: metaQuery
				}
			},
			success: function (response) {
				if (response['success']) {
					if (response['amount'] > 0) {
						if (total === 0) {
							total = response['total'];
						}
						//设置progressbar
						var percent = ((count * limit - limit) + response['amount']) / total;
						percent = percent * 100;
						var syncProgressbar = $('#beedia-sync-progressbar');
						syncProgressbar.attr({
							'aria-valuenow': percent
						});
						syncProgressbar.css({
							width: percent + '%'
						});
						syncProgressbar.text(percent.toFixed(2) + '%');
						count += 1;
						page++;
						if (resync) {
							$('#remain-amount').text(response['total'] - (((count - 1)*limit) + response['amount']));
						} else {
							$('#remain-amount').text(response['total']);
						}

						sync_to_qiniu(response['images']);
					} else {
						alert('同步完毕');
					}
				}
			}
		});
	}

	function sync_to_qiniu(images) {
		$.ajax(requestURL, {
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'beedia_sync_to_qiniu',
				images: images
			},
			success: function (response) {
				$('.beedia-qiniu-sync-switch-btn').attr({
					disabled: null
				});
				get_media_list();
			}
		});
	}

	$('.beedia-qiniu-sync-switch-btn').click(function () {
		if ($(this).hasClass('btn-danger')) {
			$(this).removeClass('btn-danger');
			$(this).addClass('btn-info');
			$(this).text('开始');
			startBtn = false;
		} else {
			$(this).removeClass('btn-info');
			$(this).addClass('btn-danger');
			$(this).text('暂停');
			startBtn = true;
			get_media_list();
		}
	});
});