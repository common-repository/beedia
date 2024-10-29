<div class="container-fluid">
	<form method="post" action="options.php">
		<?php settings_fields( 'beedia-option-group' ); ?>
		<?php do_settings_sections( 'beedia-option-group' ); ?>
		<h3>文章图片处理</h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="col">删除文章时对文章图片的处理</th>
				<td>
					<?php
					$rule = get_option('beedia_when_delete_post_image_rule', 1);
					?>
					<input type="radio" name="beedia_when_delete_post_image_rule" value="1" <?php if ($rule == 1) echo 'checked';?>> 不处理
					<input type="radio" name="beedia_when_delete_post_image_rule" value="2" <?php if ($rule == 2) echo 'checked';?>> 删除对应文章的所有图片
				</td>
			</tr>
			<tr valign="top">
				<th><h3>七牛镜像设置</h3></th>
				<td><h3><a style="color: #00adee" href="https://portal.qiniu.com/signup?code=3l7tc83w9tglu" target="_blank">去注册：送 10G 存储空间，每月 20G 免费流量</a></h3></td>
			</tr>
			<tr valign="top">
				<th scope="col">开启：请先同步完再开启</th>
				<td>
					<?php $open = get_option('beedia_qiniu_switch', 'close');?>
					<input type="radio" name="beedia_qiniu_switch" value="open" <?php if($open =='open') echo 'checked';?>> 开启
					<input type="radio" name="beedia_qiniu_switch" value="close" <?php if($open !='open') echo 'checked';?>> 关闭
				</td>
			</tr>
			<tr valign="top">
				<th scope="col">Access Key</th>
				<td>
					<input style="width: 400px" type="text" name="beedia_qiniu_access_key" value="<?php echo get_option('beedia_qiniu_access_key')?>">
				</td>
			</tr>
			<tr valign="top">
				<th scope="col">Secret Key</th>
				<td>
					<input style="width: 400px" type="text" name="beedia_qiniu_secret_key" value="<?php echo get_option('beedia_qiniu_secret_key')?>">
				</td>
			</tr>
			<tr valign="top">
				<th scope="col">空间域名</th>
				<td>
					<input style="width: 400px" type="text" placeholder="请以http或https开头" name="beedia_qiniu_host" value="<?php echo get_option('beedia_qiniu_host');?>">
				</td>
			</tr>
			<tr valign="top">
				<th scope="col">存储空间名(Bucket Name)</th>
				<td>
					<input style="width: 400px" type="text" name="beedia_qiniu_bucket_name" value="<?php echo get_option('beedia_qiniu_bucket_name')?>">
				</td>
			</tr>
			<tr valign="top">
			</tr>
		</table>
		<?php submit_button();?>
	</form>
	<input type="text" hidden id="request_url" value="<?php echo admin_url( 'admin-ajax.php' );?>">
	<p style="color: red">注意事项：</p>
	<p style="color: red">同步是对全站图片进行镜像</p>
	<p style="color: red">设定好后，首先同步原有图片至七牛云存储，同步过程中请勿关闭此页面</p>
	<p style="color: red">同步按钮：仅同步未上传的图片，重新同步按钮：强制重新同步，原来已同步的也会被覆盖</p>
	<button class="beedia-qiniu-sync btn btn-success">同步</button>  <button class="beedia-qiniu-resync btn btn-info">重新同步</button> <button class="beedia-qiniu-sync-switch-btn btn btn-danger">暂停</button> 剩余数量:<span id="remain-amount"></span><br><br>
	<div class="progress">
		<div class="progress-bar progress-bar-striped active"  id="beedia-sync-progressbar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
		</div>
	</div>
</div>
