<?php
$page_title='การแจ้งเตือน';
require_once __DIR__.'/includes/auth_check.php';
requireLogin();
require_once __DIR__.'/config/database.php';
$db=(new Database())->getConnection();
$stmt=$db->prepare('SELECT id,type,title,body,link,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([(int)$_SESSION['user_id']]);
$items=$stmt->fetchAll();
require_once __DIR__.'/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notifications.css">
<div class="notifications-page"><div class="container"><header class="notifications-page-head"><div><p class="eyebrow">NOTIFICATION CENTER</p><h1>การแจ้งเตือน</h1><p>ติดตามคำสั่งซื้อ บัญชี และกิจกรรมของร้านคุณ</p></div><button class="btn btn-outline" type="button" onclick="markAllNotifications()">ทำเครื่องหมายว่าอ่านแล้วทั้งหมด</button></header><section class="notifications-list" id="notificationsList"><?php if(!$items): ?><div class="notification-empty"><i class="fa-regular fa-bell-slash"></i><strong>ยังไม่มีการแจ้งเตือน</strong><span>เมื่อมีกิจกรรมสำคัญ ระบบจะแจ้งให้คุณทราบที่นี่</span></div><?php endif; ?><?php foreach($items as $item): ?><article class="notification-row <?php echo $item['is_read']?'':'unread'; ?>" data-notification-id="<?php echo (int)$item['id']; ?>" onclick="openNotification(this,<?php echo (int)$item['id']; ?>,'<?php echo e($item['link']??''); ?>')"><span class="notification-icon type-<?php echo e($item['type']); ?>"><i class="fa-solid <?php echo $item['type']==='order'?'fa-box':($item['type']==='payment'?'fa-money-bill-wave':($item['type']==='seller'?'fa-store':($item['type']==='security'?'fa-shield-halved':'fa-bell'))); ?>"></i></span><div><strong><?php echo e($item['title']); ?></strong><?php if($item['body']): ?><p><?php echo e($item['body']); ?></p><?php endif; ?><time><?php echo date('d/m/Y H:i',strtotime($item['created_at'])); ?></time></div><?php if(!$item['is_read']): ?><b class="notification-dot"></b><?php endif; ?></article><?php endforeach; ?></section></div></div>
<script src="<?php echo BASE_URL; ?>assets/js/notifications.js?v=1"></script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
