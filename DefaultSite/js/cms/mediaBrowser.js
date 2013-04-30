function mediaHover(m) {
	var id = m.id.replace(/media-container-/, '');
	document.getElementById('media-panel-' + id).style.display = 'block';
}

function mediaOut(m) {
	var id = m.id.replace(/media-container-/, '');
	document.getElementById('media-panel-' + id).style.display = 'none';
}

function mediaDelClicked(e, ref, id) {
	if (confirm('Are you sure you want to delete "' + ref + '"?')) {
		var cover = document.getElementById('media-cover-' + id);
		cover.innerHTML = 'Deleting...';
		cover.style.display = 'block';
		delMedia(ref);
	}
	
	// Stop event from propagating to the outter div
	if (!e) var e = window.event; 
	e.cancelBubble = true; 
	if (e.stopPropagation) e.stopPropagation();
}

function coverClicked(e) {
	// Stop event from propagating to the outter div
	if (!e) var e = window.event; 
	e.cancelBubble = true; 
	if (e.stopPropagation) e.stopPropagation();
}

function delDone(response, ajax) {
	if (response == null) { alert('Error receiving response from server'); return; }
	if (response.error != 0) { alert('The server notified an error: ' + response.errorstring); return; }
	if (response.data.result != 'ok') alert('The media could not be deleted');
	else window.location.href = '';
}

function newMediaUploaded(response, ajax) {
	if (response == null) { alert('Error receiving response from server'); return; }
	if (response.error != 0) { alert('The server notified an error: ' + response.errorstring); return; }
	if (response.data.url == '') alert('The new media could not be created');
	else window.location.href = '';
}
