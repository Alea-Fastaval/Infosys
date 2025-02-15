"use strict";
(function($) {

  $(document).ready(function() {
    $('.confirm-button').click(function() {
      confirm(this);
    });

    $('.reject-button').click(function() {
      reject(this);
    });

    $('.group-button').click(function() {
      batchConfirm(this);
    });

    $('.manual-confirm-button').click(function() {
      manualConfirm(this);
    });
  });

  function confirm(button) {
    var row = $(button).parents('tr');

    button.innerHTML = "Arbejder";
    button.disabled = true;

    var list = [
      {
        payment: row.attr('payment-id'),
        participant: row.attr('participant'),
        sheet_row: row.attr('sheet-row'),
      }
    ]

    send_confirm(button, list);
  }

  function batchConfirm(button) {
    button.innerHTML = "Arbejder";
    button.disabled = true;

    var list = [];
    
    var tableid = button.getAttribute('table');
    $('table#'+tableid+' tr.transaction-row').each(function (index, element) {
      list.push({
        payment: element.getAttribute('payment-id'),
        participant: element.getAttribute('participant'),
        sheet_row: element.getAttribute('sheet-row'),
      })
    });

    console.log(list);

    send_confirm(button, list);
  }

  function manualConfirm(button) {
    var row = $(button).parents('tr');

    button.innerHTML = "Arbejder";
    button.disabled = true;

    var list = [
      {
        payment: row.attr('payment-id'),
        participant: row.attr('participant'),
        sheet_row: row.attr('sheet-row'),
      }
    ]

    send_confirm(button, list);
  }

  function send_confirm(button, list) {
    $.ajax('/economy/confirm-payments', {
      type: 'post',
      accepts: 'application/json',
      data: {list: list},
      success: function(data, status, jqXHR) {
        confirm_success(data, button);
      },
      error: function(jqXHR, status, error) {
        alert("Der skete en fejl:" + error);
        button.innerHTML = "Fejl";
      }
    });
  }

  function confirm_success(data, button) {
    button.innerHTML = "Udført";
    for (const result of data.result) {
      if (result.status == 'success') {
        moveToTable('processed', result.sheet_row);
      } else {
        markError(result.sheet_row);
      }
    }
  }

  function reject(button) {
    var transaction = button.getAttribute('transaction');
    var participant = button.getAttribute('participant');

    moveToTable('unknown', participant,transaction)
  }

  function moveToTable(tableid, sheet_row) {
    var table = $('table#'+tableid)[0];
    
    // Create destination table if we don't have it
    if (table === undefined) {
      var category_list = $(".category-list")[0];

      var category_header = document.createElement('h3');
      if (tableid === "processed") {
        category_header.innerHTML = "Godkendte og registrerede betalinger";
        category_list.append(category_header);
      } else {
        category_header.innerHTML = "Afvist eller ingen match og skal håndteres manuelt";
        category_list.append(category_header);
        var paragraph = document.createElement('p');
        paragraph.innerHTML = "Du kan manuelt indtaste id på den deltager du vil bogføre betalingen hos";
        category_list.append(paragraph);
      }

      table = document.createElement('table');
      table.id = tableid;
      category_list.append(table);

      
      var table_header = document.createElement('thead') ;
      table_header.innerHTML = $('thead').html();
      table.prepend(table_header);

      var table_body = document.createElement('tbody');
      table.append(table_body);
    }
    var table_body = $(table).children('tbody');

    var pay_row = $(`tr.transaction-row[sheet-row=${sheet_row}]`);
    var source_tbody = pay_row.parent('tbody');
    pay_row.detach();

    pay_row.find('button').remove();
    table_body.append(pay_row);

    // If table is empty, delete it and the related elemets
    if (source_tbody.children().length === 0){
      var source_table = source_tbody.parent('table');
      var next = source_table.next();
      while (next.prop('tagName') !== 'H3' && !next.hasClass('confirm-group-top') && next.length !== 0){
        next.remove();
        next = source_table.next();
      }

      var previous =source_table.prev();
      while (previous.prop('tagName') !== 'TABLE' && !previous.hasClass('spacer') && previous.length !== 0){
        previous.remove();
        previous = source_table.prev();
      }

      source_table.remove();
    }
  }

  function updateRow(participant, transaction, info) {
    var row = $('tr[participantid='+participant+'][transactionid='+transaction+']');
    row.empty();
    row.append('<td>Deltager</td>');
    row.append('<td>'+info['name']+'</td>');
    row.append('<td>'+info['phone']+'</td>');
    row.append('<td>'+info['signup-amount']+' / '+info['real-amount']+'</td>');
    row.append('<td></td>');
    row.append('<td>ID:'+info['display-id']+'</td>');

    // <td>Deltager</td>
    // <td><?=$participant['name']?></td>
    // <td><?=$participant['phone']?></td>
    // <td><?=$participant['signup-amount']?> / <?=$participant['real-amount']?></td>
    // <td><?=$participant['comment']?></td>
    // <td>ID:<?=$participant['display-id']?></td>

  }

}) (jQuery);