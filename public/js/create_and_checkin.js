"use strict";

$(function() {
  let role_select = $('#user-category-select')
  let alea_check = $('#alea-member-checkbox')

  let single_entry = $('.single-entry')
  let all_entry = $('.all-entry')

  let single_sleep = $('.single-sleep')
  let all_sleep = $('.all-sleep')
  let any_sleep = $('.single-sleep, .all-sleep')

  let sleep_select = $('.sleep-location-wrapper')

  let create_button = $('#create-and-checkin-button')
  let continue_button = $('#checkin-continue-button')
  let cancel_button = $('#checkin-cancel-button')

  role_select.on('change', function(){
    let entry_fields = $('#entry-and-sleeping-fields')
    let organizer_fields = $('.organizer-fact')

    if ($(this).val() == 11) {
      entry_fields.hide()
    } else {
      entry_fields.show()
    }

    if ($(this).val() == 2) {
      organizer_fields.show()
      alea_check.prop('checked', true)
    } else {
      organizer_fields.hide()
    }
  })

  alea_check.on('click', function(evt) {
    if (role_select.val() == 2 && !alea_check.prop('checked')) {
      alert('Arrangører skal være medlem af Alea')
      evt.preventDefault()
    }
  })

  single_entry.on('click', (evt) => {
    if($(evt.delegateTarget).prop('checked')) {
      all_entry.prop('checked', false)
    }  
  })

  all_entry.on('click', (evt) => {
    if($(evt.delegateTarget).prop('checked')) {
      single_entry.prop('checked', false)
    }  
  })

  single_sleep.on('click', (evt) => {
    if($(evt.delegateTarget).prop('checked')) {
      all_sleep.prop('checked', false)
    }  
  })

  all_sleep.on('click', (evt) => {
    if($(evt.delegateTarget).prop('checked')) {
      single_sleep.prop('checked', false)
    }  
  })

  any_sleep.on('click', (evt) => {
    if($(evt.delegateTarget).prop('checked')) {
      show_sleep_select()
      return
    }

    let checked = false;
    any_sleep.each(function() {
      if ($(this).prop('checked')) {
        checked = true
        return false
      }
    })

    if (checked) {
      show_sleep_select()
    } else {
      sleep_select.hide()
    }
  })

  create_button.on('click', function() {
    create_participant();
  })

  cancel_button.on('click', function(){
    $('.dialog-wrapper').hide()
  })

  continue_button.on('click', function(){
    checkin_participant()
  })

  function sleep_selected() {
    if (all_sleep.prop('checked')) {
      return [true,true,true,true,true]
    }

    let days = []
    single_sleep.each(function(day, checkbox) {
      days[day] = $(checkbox).prop('checked')
    })
    return days
  }

  function show_sleep_select() {
    $.ajax({
      url: "/rooms/sleepstatsjson",
      success: function(data) {
        update_sleep_select(data)
        sleep_select.show()
      },
      error: function(jqXHR) {
        alert('kunne ikke hente sovelokale data fra infosys')
        console.log(jqXHR)
      }
    })
  }

  function update_sleep_select(data) {
    let select_input = sleep_select.find('select')
    let selected = select_input.val() ?? 70;
    select_input.html('')

    let keys = data[0]
    let rooms = data[1]

    let selected_days = sleep_selected()

    for(const [id, stats] of Object.entries(rooms)) {
      let [room_name, day_stats] = Object.entries(stats)[0]
      
      let lowest = 100000
      for(let i = 0; i < selected_days.length; i++) {
        if (!selected_days[i]) continue

        lowest = Math.min(lowest, Object.entries(day_stats)[0][1][keys[i]])
      }

      if (lowest == 0) continue

      let option = $(`<option value="${id}" ${id == selected ? "selected" : ''}>${room_name}, ${lowest} pladser</option>`)
      select_input.append(option)
    }
  }

  function create_participant() {
    let inputs 
    if (role_select.val() == 11) {
      inputs = $('#basic-info-fields input, #basic-info-fields select');
    } else {
      inputs = $('.content-container input, .content-container select');
    }
    
    let data = {}
    inputs.each(function() {
      if ($(this).attr('type') == 'checkbox') {
        data[$(this).prop('name')] = $(this).prop('checked');
      } else {
        data[$(this).prop('name')] = $(this).val();
      }
    })
    
    $.ajax({
      method: 'POST',
      data,
      success: function(data) {
        if (data.status != 'success') {
          alert(`Der skete en fejl under oprettelse af deltageren\n${data.message}`)
          return
        }

        $('#checkin-participant-id').text(data.id)
        $('#checkin-price').text(data.price)
        $('.dialog-wrapper').show();
      },
      error: function(jqXHR) {
        let data = JSON.parse(jqXHR.responseText)
        let message = data ? data.message : ''
        alert(`Der skete en fejl under oprettelse af deltageren\n${message}`)
        console.log(jqXHR)
      }
    })
  }

  function checkin_participant() {
    $.ajax({
      method: 'POST',
      data: {
        action: 'do_checkin',
        id: $('#checkin-participant-id').text(),
        price: $('#checkin-price').text(),
      },
      success: function(data) {
        if (data.status != 'success') {
          alert(`Der skete en fejl under check-in af deltageren\n${data.message}`)
          return
        }
        $('.dialog').html(`
          <p>Check in af deltager #${data.id} er udført.</p>
          <p>Genindlæs siden hvis du skal oprette flere deltagere.</p>
        `)
      },
      error: function(jqXHR) {
        let data = JSON.parse(jqXHR.responseText)
        let message = data ? data.message : ''
        alert(`Der skete en fejl under check-in af deltageren\n${message}`)
        console.log(jqXHR)
      }
    })

  }
})