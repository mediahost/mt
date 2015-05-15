function file_exists(urlToFile) {

	var xhr = new XMLHttpRequest();
	xhr.open('HEAD', urlToFile, false);
	xhr.send();

	return xhr.status !== 404;
}