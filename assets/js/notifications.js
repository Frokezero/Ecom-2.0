(() => {
  const escapeHtml = value => String(value || '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  const icon = type => type === 'order' ? 'fa-box' : type === 'payment' ? 'fa-money-bill-wave' : type === 'seller' ? 'fa-store' : type === 'security' ? 'fa-shield-halved' : 'fa-bell';
  const badge = document.getElementById('notificationBadge');
  const setBadge = count => { if (!badge) return; badge.textContent = count > 99 ? '99+' : count; badge.hidden = count < 1; };
  async function load() {
    try {
      const response = await fetch(BASE_URL + 'api/notifications.php?action=list&limit=8');
      const result = await response.json();
      if (result.status !== 'success') return;
      setBadge(result.data.unread);
      const list = document.getElementById('notificationPreviewList');
      if (!list) return;
      list.innerHTML = result.data.items.length ? result.data.items.map(item => '<a class="notification-preview-item ' + (item.is_read ? '' : 'unread') + '" href="' + escapeHtml(item.link || BASE_URL + 'notifications.php') + '" data-notification-id="' + item.id + '"><i class="fa-solid ' + icon(item.type) + '"></i><span><strong>' + escapeHtml(item.title) + '</strong><small>' + escapeHtml(item.body || '') + '</small></span></a>').join('') : '<p class="notification-preview-empty">ยังไม่มีการแจ้งเตือน</p>';
    } catch (_) { /* notification UI is non-blocking */ }
  }
  window.openNotification = async (row, id, link) => {
    try { if (row.classList.contains('unread')) {
      const body = new FormData();
      body.append('action','read'); body.append('id',id); body.append('csrf_token',CSRF_TOKEN);
      const response=await fetch(BASE_URL + 'api/notifications.php',{method:'POST',body});const result=await response.json();if(!response.ok||result.status!=='success')throw new Error(result.message||'บันทึกสถานะไม่สำเร็จ');
      row.classList.remove('unread'); row.querySelector('.notification-dot')?.remove(); load();
    } if (link) window.location.href = link; } catch(error) { if(typeof showToast==='function')showToast(error.message,'error'); }
  };
  window.markAllNotifications = async () => {
    const body = new FormData();
    body.append('action','read_all'); body.append('csrf_token',CSRF_TOKEN);
    const response=await fetch(BASE_URL + 'api/notifications.php',{method:'POST',body});const result=await response.json();if(!response.ok||result.status!=='success'){if(typeof showToast==='function')showToast(result.message||'บันทึกสถานะไม่สำเร็จ','error');return;}
    document.querySelectorAll('.notification-row.unread,.notification-preview-item.unread').forEach(item=>item.classList.remove('unread'));
    document.querySelectorAll('.notification-dot').forEach(item=>item.remove()); setBadge(0);
  };
  document.addEventListener('DOMContentLoaded', () => {
    load();
    document.getElementById('notificationTrigger')?.addEventListener('click', () => document.getElementById('notificationDropdown')?.classList.toggle('open'));
    document.addEventListener('click', event => { if (!event.target.closest('.notification-menu')) document.getElementById('notificationDropdown')?.classList.remove('open'); });
  });
})();
