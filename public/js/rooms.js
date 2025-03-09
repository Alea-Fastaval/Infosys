"use strict";

$(function() {
  var table = $('table.data-table');

  if (table.length) {
      table.DataTable({
          paging: false,
          bSearching: false,
          bProcessing: true,
          bLengthChange: false,
          iDisplayStart: 0,
          iDisplayLength: 105,
      });
  }
});