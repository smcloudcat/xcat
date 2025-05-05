/**
 * v1.1.1 | GPL-2.0 license 
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

function createRoom() {
  $.getJSON('api.php?act=create', function(res) {
    if (res.code) {
      currentCode = res.code;
      $('#codeDisplay').text(res.code).fadeIn();
      $('#qrcodeBox').slideDown();
      $('#joinBox').slideUp();
      showQRCode(res.code);
      showFileSection();
      layer.msg('房间创建成功', { icon: 1 });
    } else {
      layer.msg(res.error, { icon: 2 });
    }
  });
}

$(function() {
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

    window.verifyCaptcha = function() {
      const code = $('#captchaInput').val().trim();
      if (!code) return layer.msg('请输入验证码', { icon: 2 });

      $.post('api.php?act=captchacheck', { captcha: code }, function(res) {
        if (res.success) {
          layer.close(captchaLayer);
          createRoom();
        } else {
          layer.msg('验证码错误，请重新输入', { icon: 2 });
          $('.captcha-img').click();
          $('#captchaInput').val('');
        }
      }, 'json');
    };
  });

  $('#joinInput').on('input', function() {
    const code = $(this).val().trim();
    if (code.length === 6) {
      $.getJSON(`api.php?act=fetch&action=check&code=${code}`, function(res) {
        if (res.valid) {
          $('#joinHint').text('✓ 有效的房间码').css('color', 'var(--success-color)');
          $('#joinBtn').prop('disabled', false);
        } else {
          $('#joinHint').text('✗ 无效的房间码').css('color', '#FF5722');
          $('#joinBtn').prop('disabled', true);
        }
      });
    } else {
      $('#joinHint').text('').css('color', '');
      $('#joinBtn').prop('disabled', true);
    }
  });

  $('#joinBtn').click(function() {
    const code = $('#joinInput').val().trim();
    if (code.length !== 6) return;
    $.getJSON(`api.php?act=fetch&action=join&code=${code}`, function(res) {
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
    });
  });

  layui.use(['upload'], function() {
    const upload = layui.upload;
    const layer  = layui.layer;
    let loadIndex;

    upload.render({
      elem: '#uploadBtn',
      url: 'api.php?act=upload',
      multiple: true,
      accept: 'file',
      choose(obj) {
        loadIndex = layer.load(2);
      },
      done(res) {
        layer.close(loadIndex);
        refreshFiles();
        layer.msg('上传成功', { icon: 1 });
      },
      error() {
        layer.close(loadIndex);
        layer.msg('文件上传失败', { icon: 2 });
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
$(document).on('click', '.announcement-close', function() {
  const closeTime = Date.now();
  localStorage.setItem('announcementClosed', closeTime);
  $('#announcementBox').slideUp(300);
  console.log('公告已关闭：', new Date(closeTime).toLocaleString());
});
const storedTime = localStorage.getItem('announcementClosed');
if (storedTime) {
  const timeDiff = Date.now() - parseInt(storedTime, 10);
  const hoursDiff = timeDiff / (1000 * 60 * 60);
  if (hoursDiff < 24) {
    console.log('记录（关闭于'+ hoursDiff.toFixed(1) +'小时前）');
    $('#announcementBox').hide();
  } else {
    console.log('记录已过期（超过'+ Math.floor(hoursDiff) +'小时），清除记录');
    localStorage.removeItem('announcementClosed');
  }
} else {
  console.log('没有关闭记录');
}