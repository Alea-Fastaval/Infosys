'use strict';
$(function() {
  let table = $('.log-table').dataTable({
    bJQueryUI: true,
    sPaginationType: 'full_numbers',
    bProcessing: true,
    bLengthChange: true,
    bServerSide: true,
    sAjaxSource: page.ajax_url,
    iDisplayStart: 0,
    iDisplayLength: 10,
    iDeferLoading: page.row_count,
    aaSorting: [[0, 'desc']]
  })

  let interval_id = null
  let auto_checkbox = $('#auto-update-checkbox')
  auto_checkbox.on('click', () => {check_status()})

  function check_status() {
    if (auto_checkbox.prop('checked') && interval_id == null) {
      interval_id = setInterval(() => { update_log() }, 2000)
    }

    if (!auto_checkbox.prop('checked') && interval_id != null) {
      clearInterval(interval_id)
      interval_id = null
    }
  }

  function update_log() {
    table.fnDraw()
  }

  check_status()
})