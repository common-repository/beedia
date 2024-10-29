<?php
/*
Plugin Name: Beedia-小蜜蜂图片管理插件
Plugin URI: http://xingyue.artizen.me?source=wp&medium=beedia
Description: 小蜜蜂文章管理插件是一款媒体管理插件，目前支持删除文章的同时按照用户的设置删除该文章的图片，七牛云镜像存储功能
Version: 2.0.1
Author: 黄碧成（Bee）
Author URI: http://artizen.me?source=wp&medium=beedia
License: GPL
*/

define('BEEDIA_VERSION', '2.0.1');
// 引入七牛SDK
if (!class_exists('classLoader')) {
	require_once dirname(__FILE__) . '/lib/autoload.php';
}
use Qiniu\Auth as QNAuth;
use Qiniu\Storage\UploadManager as QNUploadManager;

if (!function_exists('beedia_qiniu_sync')) {
	function beedia_qiniu_sync($id)
	{
		// 公钥及私钥
		$accessKey = get_option('beedia_qiniu_access_key');
		$secretKey = get_option('beedia_qiniu_secret_key');
		$bucketname = get_option('beedia_qiniu_bucket_name');
		// 初始化签权对象
		$qnAuth = new QNAuth($accessKey, $secretKey);
		$token = $qnAuth->uploadToken($bucketname);

		// 需上传所有尺寸的图片
		$fileData = array();
		$fileData[] = beedia_get_image_data($id, 'full');
		$fileData[] = beedia_get_image_data($id, 'large');
		$fileData[] = beedia_get_image_data($id, 'medium');
		$fileData[] = beedia_get_image_data($id, 'thumbnail');
		$uploadedMgr = new QNUploadManager();
		$msg = array();
		foreach ($fileData as $data) {
			$fileContent = file_get_contents($data['fileUrl']);
			$msg[] = $uploadedMgr->put($token, $data['fileKey'], $fileContent);
		}
		return $msg;
	}
}
if (!function_exists('beedia_get_image_data')) {
	function beedia_get_image_data($id, $size)
	{
		$fileUrl = wp_get_attachment_image_src($id, $size);
		$fileKey = str_replace(home_url('/'), '', $fileUrl[0]);
		return array(
			'fileUrl' => $fileUrl[0],
			'fileKey' => $fileKey,
		);
	}
}

if (!is_admin()) {
	if (get_option('beedia_qiniu_switch', 'close') == 'open') {
		add_action('add_attachment', 'beedia_qiniu_sync');
		add_action('wp_loaded', 'beedia_switch_to_qiniu');
	}
}
if (!function_exists('beedia_switch_to_qiniu')) {
	function beedia_switch_to_qiniu()
	{
		ob_start('beedia_qiniu_replace');
	}
}

if (!function_exists('beedia_qiniu_replace')) {
	function beedia_qiniu_replace($html)
	{
		$qiniuHost = get_option('beedia_qiniu_host', home_url());
		$qiniuHost .= '/wp-content/uploads/';
		$html = str_replace(home_url('wp-content/uploads/'), $qiniuHost, $html);
		$html = str_replace('"/wp-content/uploads/', '"' . $qiniuHost, $html);
		$html = str_replace('"wp-content/uploads/', '"' . $qiniuHost, $html);
		$html = str_replace('//wp-content/uploads/', '/wp-content/uploads/', $html);
		return $html;
	}
}

add_action('admin_init', 'beedia_admin_init');

function beedia_admin_init()
{
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;
	if (in_array($page, array('beedia'))) {
		wp_enqueue_style('BOOTSTRAPCSS', plugins_url('/lib/bootstrap.min.css', __FILE__), array(), '3.3.7', 'screen');
		wp_enqueue_script('BOOTSTRAPJS', plugins_url('/lib/bootstrap.min.js', __FILE__), array( 'jquery'), '3.3.7', true);
		wp_enqueue_script('BEEDIAJS', plugins_url('/lib/beedia.js', __FILE__), array( 'jquery'), '3.3.7', true);
	}
	add_action('before_delete_post', 'beedia_delete_post_action');
}

if (is_admin()) {
	add_action('admin_menu', 'beedia_admin_menu');
}

if (!function_exists('beedia_admin_menu')) {
	function beedia_admin_menu()
	{
		add_menu_page('Beedia', 'Beedia', 'publish_posts', 'beedia', 'beedia_options_page');
		add_action('admin_init', 'beedia_register_option');
	}
}

if (!function_exists('beedia_options_page')) {
	function beedia_options_page()
	{
		require_once 'options-page.php';
	}
}

if (!function_exists('beedia_register_option')) {
	function beedia_register_option()
	{
		register_setting('beedia-option-group', 'beedia_when_delete_post_image_rule');
		register_setting('beedia-option-group', 'beedia_qiniu_access_key');
		register_setting('beedia-option-group', 'beedia_qiniu_secret_key');
		register_setting('beedia-option-group', 'beedia_qiniu_bucket_name');
		register_setting('beedia-option-group', 'beedia_qiniu_switch');
		register_setting('beedia-option-group', 'beedia_qiniu_host');
	}
}

if (!function_exists('beedia_delete_post_action')) {
	function beedia_delete_post_action($postId)
	{
		if (get_option('beedia_when_delete_post_image_rule', 1) == 2) {
			$images = get_attached_media('image', $postId);
			foreach ($images as $image) {
				wp_delete_attachment($image->ID);
			}
		}
	}
}


if (!function_exists('beedia_get_unused_images')) {
	function beedia_get_unused_images()
	{
		$query = array(
			'post_type' => array('attachment')
		);

		$posts = get_posts($query);
		return $posts;
	}
}
require_once( wp_normalize_path(ABSPATH).'wp-load.php');
add_action('wp_ajax_beedia_get_unused_images', 'beedia_get_unused_images');


add_action('wp_ajax_beedia_sync_to_qiniu', 'beedia_sync_to_qiniu');
if (!function_exists('beedia_sync_to_qiniu')) {
	function beedia_sync_to_qiniu()
	{
		$images =  isset($_REQUEST['images']) ? $_REQUEST['images'] : array();
		$msg = array();
		foreach ($images as $image) {
			// 上传
			$result = beedia_qiniu_sync($image['ID']);
			$result = $result[0];
			if (isset($result['hash']) && isset($result['key']) && $result['hash'] && $result['key']) {
				update_post_meta($image['ID'], 'qiniu', 1);
				$msg[$image['ID']] = array(
					'success' => true,
					'message' => $result
				);
			} else {
				$msg[$image['ID']] = array(
					'success' => false,
					'message' => $result
				);
			}
		}
		wp_send_json(array(
			'success' => true,
			'msg' => $msg
		));
	}
}

add_action('wp_ajax_beedia_query_attachments', 'beedia_query_attachments');
if (!function_exists('beedia_query_attachments')) {
	function beedia_query_attachments()
	{
		$query = $_REQUEST['query'];
		$result = new WP_Query($query);

		if ($result) {
			wp_send_json(array(
				'success' => true,
				'images' => $result->posts,
				'amount' => $result->post_count,
				'total' => intval($result->found_posts),
			));
		} else {
			wp_send_json(array(
				'success' => false,
				'msg' => '查询失败'
			));
		}
	}
}





