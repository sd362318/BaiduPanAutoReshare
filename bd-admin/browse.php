<?php
ini_set('display_errors','Off');
require 'includes/common.php';


session_start();

if(!isset($_SESSION['uid']) || !is_numeric($_SESSION['uid'])) {
	header('Location: switch_user.php');
	die();
}

if (!loginFromDatabase($_SESSION['uid'])) {
  alert_error('cookie失效，或者百度封了IP！', 'switch_user.php');
}

if (isset($_GET['switch_dir'])) {
	$_SESSION['folder'][] = urldecode($_GET['switch_dir']);
	header('Location: browse.php');
	die();
}

if (isset($_GET['goup'])) {
	array_pop($_SESSION['folder']);
	header('Location: browse.php');
	die();
}

print_header('添加文件');
if (!isset($_SESSION['folder']) || empty($_SESSION['folder']))
	$_SESSION['folder'] = ['/'];
?><h1>当前用户：<?=$username?> <a href="switch_user.php">切换</a></h1>
<h2>当前路径：<?=end($_SESSION['folder'])?></h2><p>注意：本程序无法检测到全部可能导致出问题的情况。请在主页中查看全部补档记录的可用性。</p><table border="1"><tr><th>补档</th><th>工具</th><th>文件名</th><th>fs_id</th><th>状态</th><th>访问地址</th><th>分享地址</th></tr>
<?php if (count($_SESSION['folder']) != 1) {
	echo '<tr><td colspan="7"><a href="browse.php?goup=1">[返回上层文件夹]</a></tr>';
}
$filelist = getFileList(urlencode(end($_SESSION['folder'])));
$list = getWatchlist();
$table='';

$fix = $mysql->prepare('update watchlist set name=? where fid=? and user_id=?');

foreach ($filelist as &$v) {
	if (isset($list['list'][$v['fid']])) {
		if ($list['list'][$v['fid']]['filename'] != $v['name']) {
			$fix->execute(array($v['name'],$v['fid'],$uid));
			$check_result = '<td><font color="orange">数据库中的文件名错误，已经被自动修正。</font></td>';
		} else {
			$check_result = '<td><font color="green">自动补档保护中</font></td>';
		}
		$_SESSION['file_can_add'][$v['fid']] = false;
		$check_result .= '<td><a href="'. $jumper.$list['list'][$v['fid']]['id'].'" target="_blank">'. $jumper.$list['list'][$v['fid']]['id'].'</a></td><td><a href="http://pan.baidu.com'.$list['list'][$v['fid']]['link'].'">http://pan.baidu.com'.$list['list'][$v['fid']]['link'].'</a></td>';
		unset($list['list'][$v['fid']],$list['list_filenames'][$v['fid']]);
	} else {
    if (count(array_filter($list['list_filenames'], function ($e) use ($v) {
      return strpos($e, $v['name'].'/') !== false;
    }))) {
      $check_result = '<td colspan="3"><font color="blue">文件夹内的文件被加入自动补档</font></td>';
			$_SESSION['file_can_add'][$v['fid']] = false;
		} elseif(count(array_filter($list['list_filenames'], function ($e) use ($v) {
      return strpos($v['name'], $e.'/') !== false;
    }))) {
			$check_result = '<td colspan="3"><font color="blue">父文件夹被加入自动补档</font></td>';
			$_SESSION['file_can_add'][$v['fid']] = false;
		} else {
			$check_result = '<td colspan="3">本文件未加入自动补档</td>';
			$_SESSION['file_can_add'][$v['fid']] = true;
		}
	}
	if ($_SESSION['file_can_add'][$v['fid']]) : ?>
	<tr><td><form method="post" action="add.php"><input type="hidden" name="fid" value="<?=$v['fid']?>" /><input type="hidden" name="filename" value="<?=$v['name']?>" /><input type="submit" name="submit" value="添加" /></form></td>
	<?php else : ?>
	<tr><td><input type="button" disabled="disabled" value="已添加" /></td>
<?php endif;
	if($v['isdir']) : ?>
	<td><a href="tools/share.php?<?=$v['fid']?>" target="_blank">自定义分享</a></td><td><a href="browse.php?switch_dir=<?=urlencode($v['name'].'/') ?>"><?=substr($v['name'],strlen(end($_SESSION['folder']))) ?>(文件夹)</a></td>
	<?php else : ?>
	<td><a href="tools/dl.php?<?=rawurlencode($v['name'])?>" target="_blank">下载</a>&nbsp;&nbsp;<a href="tools/share.php?<?=$v['fid']?>" target="_blank">自定义分享</a></td><td><?=substr($v['name'],strlen(end($_SESSION['folder']))) ?></td>
	<?php endif; ?>
	<td><?=$v['fid']?></td><?=$check_result?></tr>
<?php } ?>
</table>
</body></html>
