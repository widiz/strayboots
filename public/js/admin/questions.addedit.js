$(function(){
  
  $(".select2-auto").select2({
    placeholder: "Please choose one",
    allowClear: true
  });

  $('.datetimepicker').datetimepicker({
    format: 'HH:mm:ss'
  });

  var $qWrapper   = $('#qwrapper'),
    $wordInput    = $qWrapper.find('#completionWord'),
    $lettersList  = $qWrapper.find('ul.word'),
    $answers    = $('#fieldAnswers');

  $('.typeselector').change(function(){
    if (typeof window.qTypes[this.value] !== 'number')
      return;
    var type = window.qTypes[this.value];
    if (type == 2) {
      $qWrapper.show().addClass('completion');
      try {
        var o = JSON.parse($answers.val());
        if (o.w.length) {
          $wordInput.val(o.w);
          processLetters(o.l);
        }
      } catch(E) {}
    } else {
      $qWrapper.toggle(type == 0 || type == 4 || type == 5 || type == 7).removeClass('completion');
    }
    $('.xtimer').toggle(type == 4).removeClass('completion');
    $('.xchoose').toggle(type == 5).removeClass('completion');
    $('.xcheckbox').toggle(type == 7).removeClass('completion');
  }).trigger('change');

  $wordInput.keyup(function(){processLetters()});
  $lettersList.on('click', 'li', function(){
    if (this.innerText.trim() == '')
      return;
    $(this).toggleClass('correct');
    setCompletionQuestion();
  });

  function processLetters(l){
    var hasL = typeof l == 'object';
    var word = $wordInput.val().replace(/([ \-]){2,}/g, '$1');
    $lettersList.empty().append($.map(word.split(''), function(w, i){
      return '<li class="letter' + (hasL && $.inArray(i, l) >= 0 ? '' : ' correct') + '">' + (w == '-' ? ' ' : w) + '</li>';
    }));
    setCompletionQuestion();
  }

  function setCompletionQuestion(){
    var c = $.map($lettersList.children(), function(e){
      return !$(e).hasClass('correct')
    }), l = [];
    for (var i = 0; i < c.length; i++) {
      if (c[i])
        l.push(i);
    }
    var o = {
      w: $wordInput.val(),
      l: l
    };
    $answers.val(o.w == '' ? '' : JSON.stringify(o));
  }

  function readURL() {
    if (this.files && this.files[0]) {
      var reader = new FileReader(),
        self = this;
      reader.onload = function(e){
        $(self).siblings('.img-preview').css('background-image', 'url('  + e.target.result + ')').find('a').show();
      };
      reader.readAsDataURL(this.files[0]);
    }
  }
  
  $('#photo input[type="file"],#photo2 input[type="file"]').change(function(){
    readURL.apply(this);
  });
  $('#photo .img-preview a,#photo2 .img-preview a').click(function(){
    var $file = $(this).hide().parent().css('background-image', 'none').siblings('input[type="file"]').val('');
    $file.replaceWith($file.clone().change(function(){
      readURL.apply(this);
    }));
    return false;
  });

  var $attachmentType   = $('input[name="at1"]'),
    $attachmentField  = $('#fieldAttachment');

  try {
    var attachment = JSON.parse($attachmentField.val());
    if (attachment.type == 1) {
      $attachmentType.filter('[value="photo"]').prop('checked', true);
      $('#photo .img-preview').css('background-image', 'url(' + attachment.photo + ')').find('a').show();
    } else if (attachment.type == 2) {
      $attachmentType.filter('[value="vimeo"]').prop('checked', true);
      $('#vimeo input').val(attachment.video);
    } else if (attachment.type == 3) {
      $attachmentType.filter('[value="youtube"]').prop('checked', true);
      $('#youtube input').val(attachment.video);
    }
  } catch(E) {
    $attachmentType.filter('[value=""]').prop('checked', true);
  }

  $attachmentType.change(function(){
    if (!this.checked) return;
    switch (this.value) {
      case 'photo':
        $('#vimeo,#youtube').hide();
        $('#photo').show();
        break;
      case 'vimeo':
        $('#vimeo').show();
        $('#photo,#youtube').hide();
        break;
      case 'youtube':
        $('#youtube').show();
        $('#photo,#vimeo').hide();
        break;
      default:
        $('#vimeo,#photo,#youtube').hide();
    }
  }).trigger('change');
  
  var player = null,
    hasVideo = false;
  //183520231
  $('#vimeo input').change(function(){
    hasVideo = false;
    if (this.value.length) {
      try {
        if (player === null) {
          player = new Vimeo.Player($('#vimeo .video'), {
            id: this.value
          });
          player.play().then(function(id){
            $('#vimeo .video').show();
            hasVideo = true;
          });
        } else {
          player.loadVideo(this.value).then(function(id){
            player.play();
            $('#vimeo .video').show();
            hasVideo = true;
          });
        }
      } catch(E) {
        player = null;
      }
    } else {
      $('#vimeo .video').hide();
    }
  }).trigger('change');

  $('#youtube input').change(function(){
    $('#youtube .video').hide();
    hasVideo = false;
    var vidId = this.value;
    if (vidId.length) {
      /*$.ajax({
        url: "https://gdata.youtube.com/feeds/api/videos/" + vidId + "?v=2&alt=jsonc&callback=?",
        success: function(){*/
          $('#youtube iframe').unbind('load.q').bind('load.q', function(){
            $('#youtube .video').show();
            hasVideo = true;
          }).attr('src', 'https://www.youtube.com/embed/' + vidId);
        /*},
        error: function(){ }
      });*/
    } else {
      $('#youtube').hide();
    }
  }).trigger('change');

  var $attachmentType2    = $('input[name="at2"]'),
    $attachmentField2   = $('#fieldQAttachment');

  try {
    var qattachment = JSON.parse($attachmentField2.val());
    if (qattachment.type == 1) {
      $attachmentType2.filter('[value="photo"]').prop('checked', true);
      $('#photo2 .img-preview').css('background-image', 'url(' + qattachment.photo + ')').find('a').show();
    } else if (qattachment.type == 2) {
      $attachmentType2.filter('[value="vimeo"]').prop('checked', true);
      $('#vimeo2 input').val(qattachment.video);
    } else if (qattachment.type == 3) {
      $attachmentType2.filter('[value="youtube"]').prop('checked', true);
      $('#youtube2 input').val(qattachment.video);
    }
  } catch(E) {
    $attachmentType2.filter('[value=""]').prop('checked', true);
  }

  $attachmentType2.change(function(){
    if (!this.checked) return;
    switch (this.value) {
      case 'photo':
        $('#vimeo2,#youtube2').hide();
        $('#photo2').show();
        break;
      case 'vimeo':
        $('#vimeo2').show();
        $('#photo2,#youtube2').hide();
        break;
      case 'youtube':
        $('#youtube2').show();
        $('#photo2,#vimeo2').hide();
        break;
      default:
        $('#vimeo2,#photo2,#youtube2').hide();
    }
  }).trigger('change');
  
  var player2 = null,
    hasVideo2 = false;
  //183520231
  $('#vimeo2 input').change(function(){
    hasVideo2 = false;
    if (this.value.length) {
      try {
        if (player2 === null) {
          player2 = new Vimeo.Player($('#vimeo2 .video'), {
            id: this.value
          });
          player2.play().then(function(id){
            $('#vimeo2 .video').show();
            hasVideo2 = true;
          });
        } else {
          player2.loadVideo(this.value).then(function(id){
            player2.play();
            $('#vimeo2 .video').show();
            hasVideo2 = true;
          });
        }
      } catch(E) {
        player2 = null;
      }
    } else {
      $('#vimeo2 .video').hide();
    }
  }).trigger('change');

  $('#youtube2 input').change(function(){
    $('#youtube2 .video').hide();
    hasVideo2 = false;
    var vidId = this.value;
    if (vidId.length) {
      /*$.ajax({
        url: "https://gdata.youtube.com/feeds/api/videos/" + vidId + "?v=2&alt=jsonc&callback=?",
        success: function(){*/
          $('#youtube2 iframe').unbind('load.q').bind('load.q', function(){
            $('#youtube2 .video').show();
            hasVideo2 = true;
          }).attr('src', 'https://www.youtube.com/embed/' + vidId);
        /*},
        error: function(){ }
      });*/
    } else {
      $('#youtube2').hide();
    }
  }).trigger('change');

  $('#page-wrapper > .form-horizontal').submit(function(e){
    switch ($attachmentType.filter(':checked').val()) {
      case 'photo':
        if ($('#photo .img-preview a').not(':visible').length) {
          e.preventDefault();
          toastr.error(null, 'Fun fact attachment: Please enter a valid video ID and wait for the video to load');
        }
        break;
      case 'vimeo':
      case 'youtube':
        if (!hasVideo) {
          e.preventDefault();
          toastr.error(null, 'Fun fact attachment: Please enter a valid video ID and wait for the video to load');
        }
        break;
      default:
    }
    switch ($attachmentType2.filter(':checked').val()) {
      case 'photo':
        if ($('#photo2 .img-preview a').not(':visible').length) {
          e.preventDefault();
          toastr.error(null, 'Question attachment: Please enter a valid video ID and wait for the video to load');
        }
        break;
      case 'vimeo':
      case 'youtube':
        if (!hasVideo2) {
          e.preventDefault();
          toastr.error(null, 'Question attachment: Please enter a valid video ID and wait for the video to load');
        }
        break;
      default:
    }
  });

  var tagsFieldVal = $('#fieldTagsVal'),
    tagsField = $('#fieldTags');

  tagsField.change(function(){
    tagsFieldVal.val(($(this).val() || []).join(','));
  });

  tagsField.val((tagsFieldVal.val() || '').split(',').filter(function(v){
    return v;
  })).trigger('change.select2');

});