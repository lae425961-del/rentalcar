$.ajaxSetup({ headers: { 'X-CSRF-Token': $('meta[name=csrf]').attr('content') } });

/* ເລືອກ ແຂວງ -> ເມືອງ -> ບ້ານ ແບບ cascade */
function fillSelect($s, rows, key, label, ph) {
  $s.empty().append($('<option>', { value: '', text: ph }));
  rows.forEach(r => $s.append($('<option>', { value: r[key], text: r[label] })));
}
$(document).on('change', '#province', function () {
  const $d = $('#district'), $v = $('#village');
  fillSelect($v, [], 'village_id', 'village_name', '-- ເລືອກບ້ານ --');
  if (!this.value) return fillSelect($d, [], 'district_id', 'district_name', '-- ເລືອກເມືອງ --');
  $.getJSON(BASE_URL + '/api/location.php', { type: 'districts', id: this.value },
    rows => fillSelect($d, rows, 'district_id', 'district_name', '-- ເລືອກເມືອງ --'));
});
$(document).on('change', '#district', function () {
  const $v = $('#village');
  if (!this.value) return fillSelect($v, [], 'village_id', 'village_name', '-- ເລືອກບ້ານ --');
  $.getJSON(BASE_URL + '/api/location.php', { type: 'villages', id: this.value },
    rows => fillSelect($v, rows, 'village_id', 'village_name', '-- ເລືອກບ້ານ --'));
});

/* ຄິດລາຄາປະມານ ຕອນຈອງ */
function calcEstimate() {
  const rate = parseFloat($('#bookForm').data('rate')) || 0;
  const a = new Date($('[name=pickup]').val()), b = new Date($('[name=return]').val());
  if (isNaN(a) || isNaN(b) || b <= a) { $('#estimate').text('-'); return; }
  const days = Math.max(1, Math.ceil((b - a) / 86400000));
  $('#estimate').text(days + ' ວັນ × ' + rate.toLocaleString() + ' = ' + (days * rate).toLocaleString() + ' ₭');
}
$(document).on('change input', '#bookForm [type=datetime-local]', calcEstimate);

/* ປຸ່ມ ajax ທົ່ວໄປ: data-url data-confirm data-post='{"id":1}' data-input="ເຫດຜົນ" */
$(document).on('click', '.js-post', function (e) {
  e.preventDefault();
  const $b = $(this), payload = $b.data('post') || {};
  const run = (extra) => {
    $.post($b.data('url') || location.href, Object.assign({}, payload, extra))
      .done(r => {
        if (r.ok) Swal.fire({ icon: 'success', text: r.msg || 'ສຳເລັດ', timer: 1300, showConfirmButton: false }).then(() => location.reload());
        else Swal.fire({ icon: 'error', text: r.msg || 'ເກີດຂໍ້ຜິດພາດ' });
      })
      .fail(x => Swal.fire({ icon: 'error', text: (x.responseJSON && x.responseJSON.msg) || 'ເຊື່ອມຕໍ່ບໍ່ໄດ້' }));
  };
  const opt = { title: $b.data('confirm') || 'ຢືນຢັນການດຳເນີນການ?', icon: 'question', showCancelButton: true,
    confirmButtonText: 'ຢືນຢັນ', cancelButtonText: 'ຍົກເລີກ', confirmButtonColor: '#0B3C5D' };
  if ($b.data('input')) { opt.input = 'text'; opt.inputPlaceholder = $b.data('input'); }
  Swal.fire(opt).then(res => { if (res.isConfirmed) run($b.data('input') ? { note: res.value || '' } : {}); });
});
