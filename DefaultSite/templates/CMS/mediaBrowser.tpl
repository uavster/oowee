<? __move head ?><link type="text/css" rel="stylesheet" href="<? baseUrl ?>css/mediaBrowser.css">
<? __end ?>
<div class="media-browser">
<h1>Media at <span><? siteName ?></span></h1>
<? __marker loginForm ?>
<? __if isAdmin ?>
<form class="upload-panel" name="uploadMediaForm" id="uploadMediaForm" onsubmit="onNewMediaSubmit(); return false;">New file: <input type="file" name="upload"><input type="submit" value="Upload"></form>
<? __if !errorMsg ?>
<? __repeat i numMedia ?>
<div class="media-container" onmouseover="mediaHover(this)" onmouseout="mediaOut(this)" onclick="mediaClicked('<? mediaRef[i] ?>')" id="media-container-<? mediaId[i] ?>">
	<div class="cover-panel" style="display:none" id="media-cover-<? mediaId[i] ?>" onclick="coverClicked(event)"></div>
	<div class="media-panel" id="media-panel-<? mediaId[i] ?>" style="display:none">
		<div class="media-info"><? mediaInfo[i] ?></div>
		<div class="media-button" onclick="mediaDelClicked(event, '<? mediaRef[i] ?>', <? mediaId[i] ?>)">X</div>
	</div>
	<? __if isImage[i] ?><img src="<? mediaSource[i] ?>" width="<? mediaWidth[i] ?>"><? __end ?>
	<? __if isFlash[i] ?><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" width="<? mediaWidth[i] ?>" height="100%"><param name="quality" value="high" /><param name="movie" value="<? mediaSource[i] ?>" /><embed pluginspage="http://www.macromedia.com/go/getflashplayer" quality="high" src="<? mediaSource[i] ?>" type="application/x-shockwave-flash" width="<? mediaWidth[i] ?>" height="100%"></embed></object><? __end ?>
</div>
<? __end ?>
<? __end ?>
<? __if errorMsg ?>
<div class="error-msg"><? errorMsg ?></div>
<? __end ?>
<? __end ?>
</div>
