"using strict";

$(function() {
  // .editable(window.infosys_data.participant_editable_url, {
  //     type: 'select',
  //     data: '{"they": "De/Dem", "her": "Hun/Hende", "he": "Han/Ham"}',
  //     submit: "Ok",
  //     indicator: 'Saving ...',
  //     tooltip: 'Click to edit',
  //     onerror: updateError
  // });


  single_pronouns = {
    none : {
      text: { en: "None", da: "Ingen" },
    },
    ns : { // Neutral Subject
      text: { en: "They", da: "De" },
    },
    no : { // Neutral Object
      text: { en: "Them", da: "Dem" },
    },
    fs : { // Feminine Subject
      text: { en: "She", da: "Hun" },
    },
    fo : { // Feminine Object
      text: { en: "Her", da: "Hende" },
    },
    ms : { // Masculine Subject
      text: { en: "He", da: "Han" },
    },
    mo : { // Masculine Object
      text: { en: "Him", da: "Ham" },
    },
    ask : {
      text: { en: "Ask me", da: "Spørg mig" },
    }
  }

  let pronoun_element = $('td.editable.pronoun')
  pronoun_element.on('click', () => {
    if (pronoun_element.prop('editing')) return

    init_form(pronoun_element.attr('data'))
    pronoun_element.text('')
    pronoun_element.append(form_element)
    form_element.on('submit', (e) => {submit_changes(e)})
    select1.on('change', () => { change1() })
    select2.on('change', () => { change2() })
    pronoun_element.prop('editing', true)
  })

  $(window).on('keydown', (evt) => {
    if (!pronoun_element.prop('editing')) return
    if (evt.key != 'Escape') return

    reset_element()
  })

  let form_element = $('<form id="pronouns-edit-form" style="display:inline-flex;margin:0;align-items:baseline;"></form>')

  let select1 = $(`<select id="pronoun-select1" name="pronoun-select1" style="margin:0;width:6em;"></select>`)
  form_element.append(select1)

  let divider = $('<p style="margin:0 .5rem">/</p>')
  form_element.append(divider)

  let select2 = $(`<select id="pronoun-select2" name="pronoun-select2" style="margin:0;width:6em;"></select>`)
  form_element.append(select2)

  let submit = $('<button type="submit" style="margin-left:.5rem">OK</button>')
  form_element.append(submit)
  
  let lang = $('td#main_lang').text()
  update_selectors(lang)

  function update_selectors(lang) {
    select1.html('')
    for (const [key, pronoun] of Object.entries(single_pronouns)) {
      select1.append(`<option value="${key}" id="pronoun-select1-option-${key}">${pronoun.text[lang]}</option>`)
    } 

    select2.html('')
    select2.append(`<option value="" id="pronoun-select1-option-emprty"></option>`)

    let exclude = {none:true, ask:true}
    for (const [key, pronoun] of Object.entries(single_pronouns)) {
      if (exclude[key]) continue
      select2.append(`<option value="${key}" id="pronoun-select1-option-${key}" >${pronoun.text[lang]}</option>`)
    } 
  }

  function change1() {
    let val = select1.val()
    let part2_hidden = select2.is(':hidden');

    if (val == "none" || val == "ask") {
      select2.hide()
      divider.hide()
      select2.val('')
      set_disabled('', select1)
    } else {
      select2.show()
      divider.show()
    }

    // Did we unhide the 2nd select?
    if (part2_hidden && !select2.is(':hidden'))  {
      let pair = { ns : "no", fs : "fo", ms : "mo" }

      // Set 2nd select to most common pair
      if (pair[val]) {
        select2.val(pair[val])
      }
    }

    set_disabled(val, select2)
  }

  function change2() {
    let val = select2.val()
    set_disabled(val, select1)
  }

  //  Disable option with value = val and enable all other options
  function set_disabled(val, select) {
    select.find('option').each(( _ , option) => {
      option = $(option)
      option.prop('disabled', option.val() == val)
    })
  }

  function get_selection() {
    let value = select1.val()
    let text = select1.find('option:selected').text()

    if (value != "none" && value != "ask") {
      value += select2.val()
      text += "/"+select2.find('option:selected').text()
    }

    return { value, text }
  }

  function init_form(value) {
    if (value == "ask" || value == "none") {
      select1.val(value)
      change1()
      return
    }

    if (value.length == 2 && single_pronouns[value]) {
      select1.val(value)
      change1()
      select2.val("")
      return
    }

    if (value.length == 4) {
      let part1 = value.substring(0,2)
      let part2 = value.substring(2,4)

      if (single_pronouns[part1] && single_pronouns[part2]) {
        select1.val(part1)
        change1()

        select2.val(part2)
        change2()
        return
      }
    }

    select1.val("none")
    change1()
    return
  }

  function submit_changes(evt) {
    evt.preventDefault()
    pronoun_element.text('Saving ...')

    $.ajax({
      url: window.infosys_data.participant_editable_url,
      method: 'POST',
      data: {
        id: 'pronouns',
        value: get_selection().value
      },
      error: (jqXHR) => {
        pronoun_element.text('#ERROR#')
        console.log('AJAX error:', jqXHR)
      },
      success: (data) => {
        console.log('AJAX data:', data)
        pronoun_element.attr('data', data)
        init_form(data)
        pronoun_element.text(get_selection().text)
        pronoun_element.prop('editing', '')
      }
    })
  }

  function reset_element() {
    init_form(pronoun_element.attr('data'))
    pronoun_element.text(get_selection().text)
    pronoun_element.prop('editing', '')
  }
})
