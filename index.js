/**
 * v1.0.0 | GPL-2.0 license 
 * emial：yuncat@email.lwcat.cn
 */
let currentCode = '';
let qrVisible = false;

function toggleQRCode() {
  qrVisible = !qrVisible;
  $('#qrcodeContent').slideToggle(300);
  $('.qrcode-toggle').html(qrVisible ? '▲ 收起二维码 ▲' : '▼ 扫描二维码加入 ▼');
}

function showQRCode(code) {
  const url = window.location.origin + window.location.pathname + '?code=' + code;
  new QRious({ 
    element: document.getElementById('qrcode'),
    size: 180,
    value: url,
    backgroundAlpha: 0,
    foreground: '#000000'
  });
}

function showFileSection(){
  $('#fileSection').fadeIn();
  $('#createBtn, #joinBtn, #joinInput').prop('disabled', true);
}

function refreshFiles(){
  if(!currentCode) return;
  $.getJSON('fetch.php', function(res){ 
    if(res.files) renderFiles(res.files); 
  });
}

// 渲染文件列表，使用安全下载接口
function renderFiles(files){
  let rows = '';
  files.forEach(f => {
    rows += `<tr>
              <td><a href="download.php?file=${encodeURIComponent(f.stored)}" download="${f.name}">${f.name}</a></td>
              <td>${f.time}</td>
              <td>${f.uploader}</td>
            </tr>`;
  });
  $('#fileTable').html(rows);
}

$(function() {
  const getQueryParam = (name) => {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  };
  const preCode = getQueryParam('code');
  if (preCode && preCode.length === 6) {
    $('#joinInput').val(preCode).trigger('input');
    setTimeout(() => { $('#joinBtn').click(); }, 500);
  }

  $('#createBtn').click(function(){
    $.getJSON('create.php', function(res){
      if(res.code){
        currentCode = res.code;
        $('#codeDisplay').text(res.code).fadeIn();
        $('#qrcodeBox').slideDown();
        $('#joinBox').slideUp();
        showQRCode(res.code);
        showFileSection();
        layer.msg('房间创建成功', {icon: 1});
      }
    });
  });

  $('#joinInput').on('input', function(){
    let code = $(this).val().trim();
    if(code.length === 6){
      $.getJSON('fetch.php?action=check&code=' + code, function(res){
        if(res.valid) {
          $('#joinHint').text('✓ 有效的房间码').css('color','var(--success-color)');
          $('#joinBtn').prop('disabled', false);
        } else {
          $('#joinHint').text('✗ 无效的房间码').css('color','#FF5722');
          $('#joinBtn').prop('disabled', true);
        }
      });
    } else {
      $('#joinHint').text('').css('color','');
      $('#joinBtn').prop('disabled', true);
    }
  });

  $('#joinBtn').click(function(){
    let code = $('#joinInput').val().trim();
    if(code.length !== 6) return;
    $.getJSON('fetch.php?action=join&code=' + code, function(res){
      if(res.success){
        currentCode = code;
        $('#createBox').slideUp();
        $('#codeDisplay').text(code).show();
        showFileSection();
        renderFiles(res.files);
        layer.msg('成功加入房间', {icon: 1});
      } else {
        layer.msg(res.msg || '加入失败', {icon: 2});
      }
    });
  });

  layui.use(['upload','element'], function(){
    let upload = layui.upload, element = layui.element;
    upload.render({
      elem: '#uploadBtn',
      url: 'upload.php',
      multiple: true,
      accept: 'file',
      choose: function(obj){
        $('.upload-progress').show();
        obj.preview(function(index, file, result){
          element.progress('mainProgress', '0%');
        });
      },
      progress: function(index, percent){
        element.progress('mainProgress', percent + '%');
      },
      done: function(res){ 
        refreshFiles();
        element.progress('mainProgress', '100%');
        setTimeout(() => $('.upload-progress').hide(), 1000);
      },
      error: function(){
        layer.msg('文件上传失败', {icon: 2});
      }
    });
  });

  setInterval(refreshFiles, 5000);

  function adjustLayout() {
    $('.card-wrapper').css('max-width', 
      $(window).width() < 768 ? '100%' : '600px'
    );
  }
  $(window).on('resize', adjustLayout);
  adjustLayout();
});