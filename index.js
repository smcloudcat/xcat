/**
 * v1.1.2 | GPL-2.0 license 
 * emial：yuncat@email.lwcat.cn
 */
let currentCode = '';
let qrVisible = false;
let isDragging = false;

function initDragUpload() {
  document.addEventListener('dragover', function(e) {
    e.preventDefault();
    if (!isDragging && !currentCode) {
      isDragging = true;
      $('body').addClass('dragging-over');
    }
  });

  document.addEventListener('dragleave', function(e) {
    if (isDragging) {
      isDragging = false;
      $('body').removeClass('dragging-over');
    }
  });

  document.addEventListener('drop', function(e) {
    e.preventDefault();
    isDragging = false;
    $('body').removeClass('dragging-over');
    
    if (!currentCode) {
      layer.msg('请先创建或加入房间', {icon: 2});
      return;
    }

    const files = e.dataTransfer.files;
    if (files.length === 0) return;

    const load = layer.load(2);
    Promise.all(Array.from(files).map(file => uploadFileInChunks(file)))
      .then(() => {
        layer.msg('上传成功', {icon: 1});
        refreshFiles();
      })
      .catch(err => {
        layer.msg('上传失败: ' + err.message, {icon: 2});
      })
      .finally(() => {
        layer.close(load);
      });
  });
}

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
    foreground: '#000'
  });
}

function showFileSection() {
  $('#fileSection').fadeIn();
  $('#createBtn, #joinBtn, #joinInput').prop('disabled', true);
}

function refreshFiles() {
  if (!currentCode) return;
  
  $.getJSON('api.php?act=fetch', function(res) {
    if (res.files) renderFiles(res.files);
  }).fail(function() {
    layer.msg('获取文件列表失败', {icon: 2});
  });
}

function renderFiles(files) {
  let rows = '';
  files.forEach(f => {
    rows += `
      <tr>
        <td>
          <a href="api.php?act=download&file=${encodeURIComponent(f.stored)}"
             download="${f.name}">
            ${f.name}
          </a>
        </td>
        <td>${f.time}</td>
        <td>${f.uploader}</td>
      </tr>`;
  });
  $('#fileTable').html(rows);
}

async function createRoom() {
  const load = layer.load(2);
  try {
    const res = await $.getJSON('api.php?act=create');
    if (res.code) {
      currentCode = res.code;
      $('#codeDisplay').text(res.code).fadeIn();
      $('#qrcodeBox').slideDown();
      $('#joinBox').slideUp();
      showQRCode(res.code);
      showFileSection();
      layer.msg('房间创建成功', { icon: 1 });
    } else {
      layer.msg(res.error || '创建房间失败', { icon: 2 });
    }
  } catch (error) {
    layer.msg('请求失败，请重试', { icon: 2 });
  } finally {
    layer.close(load);
  }
}

async function uploadFileInChunks(file) {
  const CHUNK_SIZE = 2 * 1024 * 1024;
  const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
  const uploadId = Date.now() + '_' + Math.random().toString(36).substr(2, 5);

  for (let index = 0; index < totalChunks; index++) {
    const start = index * CHUNK_SIZE;
    const end = Math.min(file.size, start + CHUNK_SIZE);
    const chunk = file.slice(start, end);

    const form = new FormData();
    form.append('uploadId', uploadId);
    form.append('fileName', file.name);
    form.append('chunkIndex', index);
    form.append('totalChunks', totalChunks);
    form.append('chunkData', chunk);
    form.append('code', currentCode);

    const res = await fetch('api.php?act=upload', {
      method: 'POST',
      body: form
    });
    const json = await res.json();
    if (json.status !== 'ok') {
      throw new Error(json.msg || '上传失败');
    }
  }

  const mergeForm = new FormData();
  mergeForm.append('uploadId', uploadId);
  mergeForm.append('fileName', file.name);
  mergeForm.append('totalChunks', totalChunks);
  mergeForm.append('code', currentCode);
  
  const mergeRes = await fetch('api.php?act=merge', {
    method: 'POST',
    body: mergeForm
  });
  const mergeJson = await mergeRes.json();
  if (mergeJson.status !== 'ok') {
    throw new Error(mergeJson.msg || '合并文件失败');
  }
}

$(function() {
  initDragUpload();
  
  $(document).on('click', '.announcement-close', function() {
    const closeTime = Date.now();
    localStorage.setItem('announcementClosed', closeTime);
    $('#announcementBox').slideUp(300);
  });

  const storedTime = localStorage.getItem('announcementClosed');
  if (storedTime) {
    const timeDiff = Date.now() - parseInt(storedTime, 10);
    if (timeDiff < 24 * 60 * 60 * 1000) {
      $('#announcementBox').hide();
    } else {
      localStorage.removeItem('announcementClosed');
    }
  }

  const getQueryParam = name => new URLSearchParams(window.location.search).get(name);
  const preCode = getQueryParam('code');
  if (preCode && preCode.length === 6) {
    $('#joinInput').val(preCode).trigger('input');
    setTimeout(() => $('#joinBtn').click(), 500);
  }

  $('#createBtn').click(function() {
    const captchaLayer = layer.open({
      type: 1,
      title: '请输入验证码',
      area: ['320px', '280px'],
      content: `<div class="captcha-modal">
        <img src="api.php?act=captcha&t=${Date.now()}" class="captcha-img"
             onclick="this.src='api.php?act=captcha&t='+Date.now()">
        <input type="text" id="captchaInput"
               class="layui-input captcha-input"
               placeholder="输入验证码" maxlength="6">
        <button onclick="verifyCaptcha()"
                class="layui-btn layui-btn-normal"
                style="margin-top:15px;">验证</button>
      </div>`,
      success(layero) {
        layero.find('.captcha-img').click(function() {
          $(this).attr('src', 'api.php?act=captcha&t=' + Date.now());
        });
      }
    });

    window.verifyCaptcha = async function() {
      const code = $('#captchaInput').val().trim();
      if (!code) return layer.msg('请输入验证码', { icon: 2 });

      const load = layer.load(2);
      try {
        const res = await $.post('api.php?act=captchacheck', { captcha: code });
        if (res.success) {
          layer.close(captchaLayer);
          await createRoom();
        } else {
          layer.msg('验证码错误，请重新输入', { icon: 2 });
          $('.captcha-img').click();
          $('#captchaInput').val('');
        }
      } catch (error) {
        layer.msg('验证失败，请重试', { icon: 2 });
      } finally {
        layer.close(load);
      }
    };
  });
  $('#joinInput').on('input', function() {
    const code = $(this).val().trim();
    if (code.length === 6) {
      const load = layer.load(2);
      $.getJSON(`api.php?act=fetch&action=check&code=${code}`)
        .done(function(res) {
          if (res.valid) {
            $('#joinHint').text('✓ 有效的房间码').css('color', 'var(--success-color)');
            $('#joinBtn').prop('disabled', false);
          } else {
            $('#joinHint').text('✗ 无效的房间码').css('color', '#FF5722');
            $('#joinBtn').prop('disabled', true);
          }
        })
        .fail(function() {
          $('#joinHint').text('验证失败').css('color', '#FF5722');
          $('#joinBtn').prop('disabled', true);
        })
        .always(() => layer.close(load));
    } else {
      $('#joinHint').text('').css('color', '');
      $('#joinBtn').prop('disabled', true);
    }
  });
  $('#joinBtn').click(async function() {
    const code = $('#joinInput').val().trim();
    if (code.length !== 6) return;
    
    const load = layer.load(2);
    try {
      const res = await $.getJSON(`api.php?act=fetch&action=join&code=${code}`);
      if (res.success) {
        currentCode = code;
        $('#createBox').slideUp();
        $('#codeDisplay').text(code).show();
        showFileSection();
        renderFiles(res.files);
        layer.msg('成功加入房间', { icon: 1 });
      } else {
        layer.msg(res.msg || '加入失败', { icon: 2 });
      }
    } catch (error) {
      layer.msg('请求失败，请检查网络', { icon: 2 });
    } finally {
      layer.close(load);
    }
  });
  $('#uploadBtn').off('click').on('click', function() {
    if (!currentCode) {
      layer.msg('请先创建或加入房间', {icon: 2});
      return;
    }
    
    const $input = $('<input type="file" multiple style="display:none">');
    $('body').append($input);
    $input.on('change', async function() {
      const files = Array.from(this.files);
      if (!files.length) return;
      
      const load = layer.load(2);
      try {
        await Promise.all(files.map(file => uploadFileInChunks(file)));
        layer.msg('上传成功', { icon: 1 });
        refreshFiles();
      } catch (error) {
        layer.msg('上传失败: ' + error.message, { icon: 2 });
      } finally {
        layer.close(load);
        $input.remove();
      }
    });
    $input.click();
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