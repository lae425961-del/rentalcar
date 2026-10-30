/* ແຈ້ງເຕືອນ: poll ທຸກ 20 ວິນາທີ */
let lastCount = null;
const ICONS = { new_member: 'bi-person-plus', new_booking: 'bi-calendar-plus', booking_cancel: 'bi-calendar-x', overdue: 'bi-exclamation-triangle', payment: 'bi-cash' };
const LINKS = { customers: 'customers.php', bookings: 'bookings.php', rentals: 'rentals.php' };

function loadNotif(showToast) {
  $.getJSON(BASE_URL + '/api/notifications.php', { action: 'list' }, r => {
    if (!r.ok) return;
    const $c = $('#bellCount');
    r.unread > 0 ? $c.text(r.unread).removeClass('d-none') : $c.addClass('d-none');
    if (showToast && lastCount !== null && r.unread > lastCount) {
      const n = r.items[0];
      Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: n.title, text: n.message, showConfirmButton: false, timer: 4500 });
    }
    lastCount = r.unread;
    const $l = $('#notifList').empty();
    if (!r.items.length) $l.append('<div class="p-3 text-muted small">ບໍ່ມີແຈ້ງເຕືອນ</div>');
    r.items.forEach(n => {
      const href = (LINKS[n.ref_table] ? BASE_URL + '/admin/' + LINKS[n.ref_table] : '#');
      $l.append($('<a class="notif-item"></a>').addClass(n.is_read == 0 ? 'unread' : '').attr('href', href).attr('data-id', n.notification_id)
        .append($('<div class="fw-medium"></div>').html('<i class="bi ' + (ICONS[n.type] || 'bi-bell') + '"></i> ').append(document.createTextNode(n.title)))
        .append($('<small></small>').text(n.message + ' · ' + n.created_at)));
    });
  });
}
$(document).on('click', '.notif-item', function () {
  $.post(BASE_URL + '/api/notifications.php', { action: 'read', id: $(this).data('id') });
});
$('#readAll').on('click', function (e) {
  e.preventDefault();
  $.post(BASE_URL + '/api/notifications.php', { action: 'read_all' }, () => loadNotif(false));
});
loadNotif(false);
setInterval(() => loadNotif(true), 20000);
