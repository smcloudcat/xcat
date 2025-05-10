<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>XCAT文件传输助手</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <meta name="keywords" content="XCAT文件传输助手,面对面文件传输,实时文件传输,文件互传">
  <meta name="description" content="XCAT文件传输助手是一个实时文件互传的工具，无需登录任何账号，易操作，两台设备快速传输文件">
  <link rel="stylesheet" href="https://cdn.lwcat.cn/layui/css/layui.css">
  <link rel="stylesheet" href="https://cdn.lwcat.cn/XCAT/1.1.2/index.css">
  <link rel="icon" type="image/ico" href="/favicon.ico">
<!--  <link rel="stylesheet" href="index.css">-->
</head>
<body>
<div class="container-wrapper">
  <div class="layui-container">
    <h1 class="main-title">XCAT文件传输助手</h1>
    
<div class="card-wrapper" id="announcementBox">
  <div class="layui-card">
<div class="layui-card-header">
  <i class="layui-icon layui-icon-notice"></i> 系统公告
  <button type="button" class="announcement-close">
    <i class="layui-icon layui-icon-close"></i>
  </button>
</div>
    <div class="layui-card-body">
      <div class="announcement-content">
        <p>📢 最新公告：</p>
        <P>当前版本为1.1.2</P>
      </div>
    </div>
  </div>
</div>

    <div class="card-wrapper" id="createBox">
      <div class="layui-card">
        <div class="layui-card-header"><i class="layui-icon layui-icon-add-circle"></i> 创建新房间</div>
        <div class="layui-card-body">
          <button id="createBtn" class="layui-btn layui-btn-fluid layui-btn-normal layui-btn-lg">创建房间</button>
          <div class="code-badge" id="codeDisplay"></div>
          <div class="qrcode-container" id="qrcodeBox" style="display:none;">
            <a class="qrcode-toggle" onclick="toggleQRCode()">▼ 扫描二维码加入 ▼</a>
            <div style="display:none;text-align:center;" id="qrcodeContent">
              <canvas id="qrcode"></canvas>
              <p style="text-align:center;margin-top:15px;color:#666;">使用手机扫描二维码加入房间</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-wrapper" id="joinBox">
      <div class="layui-card">
        <div class="layui-card-header"><i class="layui-icon layui-icon-user"></i> 加入已有房间</div>
        <div class="layui-card-body">
          <div class="layui-form-item">
            <div style="width:100%;">
              <input type="text" id="joinInput" placeholder="请输入6位房间码" 
                     class="layui-input layui-input-lg layui-btn-fluid" 
                     style="text-align:center;letter-spacing:5px;">
            </div>
            <div style="margin-top:15px;">
              <button id="joinBtn" class="layui-btn layui-btn-fluid layui-btn-warm" disabled>立即加入</button>
            </div>
            <div style="margin-top:10px;min-height:22px;">
              <span id="joinHint" class="layui-font-12"></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="fileSection" class="card-wrapper">
      <div class="layui-card">
        <div class="layui-card-header"><i class="layui-icon layui-icon-upload-drag"></i> 文件传输区</div>
        <div class="layui-card-body">
          <button id="uploadBtn" class="layui-btn layui-btn-primary layui-btn-fluid">
            <i class="layui-icon layui-icon-upload"></i> 选择文件上传
          </button>
          <table class="layui-table" lay-skin="line" style="margin-top:20px;">
            <colgroup>
              <col width="50%">
              <col width="25%">
              <col width="25%">
            </colgroup>
            <thead>
              <tr><th>文件名</th><th>上传时间</th><th>上传者</th></tr>
            </thead>
            <tbody id="fileTable"></tbody>
          </table>
        </div>
      </div>
    </div>
    <div style="text-align: center; padding: 30px 0; color: #999; font-size: 14px; border-top: 1px solid #eee; margin-top: 30px;">
      © 2025 XCAT文件传输助手. All Rights Reserved.
    </div>
  </div>
</div>
</div>
</div>

<script src="https://cdn.lwcat.cn/jquery/jquery.js"></script>
<script src="https://cdn.lwcat.cn/layui/layui.js"></script>
<script src="https://cdn.lwcat.cn/qrious/qrious.min.js"></script>
<script src="https://cdn.lwcat.cn/XCAT/1.1.2/index.js"></script>
<!--<script src="index.js"></script>-->
</body>
</html>