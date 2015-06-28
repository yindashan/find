<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <link rel="apple-touch-icon" sizes="114x114" href="" />
    <link rel="apple-touch-startup-image" href="" />
    <title></title>
    <link type="text/css" href="/statics/css/thickbox.css" rel="stylesheet" />
    <script type="text/javascript" src="/statics/js/jquery.min.js"></script>
<script type="text/javascript" src="/statics/js/thickbox.js"></script>
</head>
<body>
<div id="pics">
    <div class="pic">
    <a href="<?=$content['imgs'][0]['n']['url']?>" title="test test" class="thickbox"><img alt="check test" src="<?=$content['imgs'][0]['s']['url']?>" /></a>
    </div>
        <div class="mask"><p><?=$content['imgs'][0]['content']?></p></div>
    </div>
    <div class="pic_list">
        <ul id="thumbnail">
        <?php foreach($content['imgs'] as $img): ?>
        <?php 
            if(strstr($img['t']['url'],'@'))
            {
                $imgUrl = explode("@",$img['t']['url']);
                $img['t']['url'] = $imgUrl[0];
            }
        ?>
        <li data-src="<?=$img['content']?>"><img src="<?=$img['t']['url']."@150-100-200-200a"?>" ></li>
        <?php endforeach?>
        </ul>
   <script>
$('#thumbnail li img').each(function() {
      $(this).css("height",$('#thumbnail li:first-child').width());
   })
   </script>
    </div>
<?php if(!empty($content['tags'])): ?>
    <div class="pic_tag">
<?php foreach ($content['tags'] as $tag): ?>
<?=$tag?>&nbsp;&nbsp;&nbsp;
<?php endforeach; ?>
    </div>
<?php endif; ?>
    <div class="line"></div>
</div>
<div id="brief">
<div class="head"><img height="70" width="70" src="<?=$content['avatar']?>"> </div>
    <div class="descrion">
    <h1><?=$content['sname']?></h1><span></span>
    <p><?=$content['intro']?></p>
    </div>
</div>
<div id="comment">
    <ul>
        <?php foreach($comment as $cmt): ?>
        <li>
        <span class="head"><img height="60" width="60" src="<?=$cmt['avatar']?>" ></span>
            <span class="comment_content">
                <h2><?=$cmt['sname']?>：</h2>
                <p><?=$cmt['content']?></p>
            </span>
        </li>
        <?php endforeach?>
    </ul>
</div>
</body>
</html>
