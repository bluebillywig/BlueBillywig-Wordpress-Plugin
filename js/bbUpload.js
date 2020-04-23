var $ = jQuery;
var startButton = $('#bb-start-upload-button');
var uploader;

// Metadata
var titleField = $('#bb-upload-title');
var descField = $('#bb-upload-description');
var statusField = $('#bb-upload-status');
var fileField = document.getElementById('bb-upload-file');

// Progress bar
var progressWrapper = $('#bb-progress-wrapper');
var progressBar = $('#bb-progressbar-wrapper');
var progressAction = $('#bb-progressbar-action');
var progressBarFill = $('#bb-progressbar-fill');
var progressBarAmount = $('#bb-progressbar-amount');

// Notice
var noticeDisplay = $('#bb-upload-notice');
var notices = [];

// Controls
var controlWrapper = $('#bb-upload-controls');
var pauseButton = $('#bb-pause-upload-button');
var cancelButton = $('#bb-cancel-upload-button');

// Data 
var fileSelect = $('#bb-upload-file');
var file, title, mediaclipId, description, status;
var currentFileId;

const partSize = (5 * 1024 * 1024);

// Wait for fineuploader to load
$(document).ready(function () {
    startButton.click(function () {
        resetPage();
        initUpload();
    });

    pauseButton.click(togglePause);
    cancelButton.click(cancelUpload);
});

function initUpload() {
    if (!titleField.val() || !fileSelect.val()) {
        var missing = 'Missing the following values for upload:';
        missing += !titleField.val() ? "<br/><b>- Title</b>" : '';
        missing += !fileSelect.val() ? "<br/><b>- Video File</b>" : '';
        showNotice(missing, 'error');
        return;
    }

    progressAction.text('Creating mediaclip in OVP..');
    progressWrapper.css('display', 'block');

    toggleControlBar(true);
    toggleControlBarButtons(false);

    file = fileField.files[0];
    title = titleField.val();
    description = descField.val();
    status = statusField.val();

    try {
        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'create_media_clip_request',
                'filename': file.name,
                'title': title,
                'description': description,
                'status': status
            },
            success: function (response) {
                response = JSON.parse(response);
                mediaclipId = response.id;

                var data = {
                    mediaclipId: mediaclipId
                };
                fetchEndpoint(data);
            },
            error: function (error) {
                console.log(error);
            }
        });
    } catch (exception) {
        showNotice('Error during uploading: ' + exception, 'error');
    }
}

function fetchEndpoint(data) {
    progressAction.text('Retrieving endpoint data..');
    try {
        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'fetch_upload_endpoint_request',
                'mediaclipId': data.mediaclipId
            },
            success: function (response) {
                response = JSON.parse(response);
                if (response.awsResponse.endpoint && response.awsResponse.currentDate && response.awsResponse.accessKey && response.awsResponse.uploadIdentifier) {
                    startUpload(response.rpctoken, response.awsResponse);
                } else {
                    onError(null, null, "Failed to fetch endpoint");
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    } catch (exception) {
        showNotice('Error during uploading: ' + exception, 'error');
    }
}

function startUpload(rpcToken, awsResponse) {
    progressAction.text('Starting upload..');

    uploader = new qq.s3.FineUploaderBasic({
        request: {
            endpoint: 'https://' + (file.size > partSize ? awsResponse.endpoint.split('/').shift() : awsResponse.endpoint),
            clockDrift: new Date(awsResponse.currentDate).getTime() - Date.now(),
            accessKey: awsResponse.accessKey,
            params: {
                'uploadIdentifier': awsResponse.uploadIdentifier,
                'Content-Disposition': 'attachment; filename=' + encodeURIComponent(file.name)
            }
        },
        cors: {
            expected: true,
            sendCrendentials: true
        },
        objectProperties: {
            acl: awsResponse.acl,
            region: awsResponse.region,
            bucket: awsResponse.endpoint.split('/').pop(),
            key: function (id) {
                currentFileId = id;

                var fileForKey = file;
                var parts = fileForKey.name.split('.');

                if (fileForKey.size < partSize) {
                    return awsResponse.path + '/' + awsResponse.uploadIdentifier + (parts.length > 1 ? '.' + parts.pop() : '');
                } else {
                    return awsResponse.endpoint.split('/').pop() + '/' + awsResponse.path + '/' +
                        awsResponse.uploadIdentifier + (parts.length > 1 ? '.' + parts.pop() : '');
                }
            }
        },
        chunking: {
            enabled: (file.size > partSize),
            concurrent: {
                enabled: true
            },
            partSize: partSize
        },
        resume: {
            enabled: true
        },
        retry: {
            enableAuto: false
        },
        signature: {
            endpoint: 'https://' + publicationStub + '.bbvms.com/sapi/awsupload?mediaclipId=' + mediaclipId + '&rpctoken=' + rpcToken,
            version: 4
        }, //TODO: use api settings for url
        debug: false,
        callbacks: {
            onSubmit: onSubmit,
            onUpload: onUpload,
            onProgress: onProgress,
            onError: onError,
            onComplete: onComplete,
            onCancel: onCancel
        }
    });
    uploader.addFiles([file]);
}

function onSubmit(uploadIdentifier, fn) {
    //Reset form
    fileField.value = '';
    titleField.val('');
    descField.val('');

    hideNotice();
}

function onUpload(uploadIdentifier, fn) {
    progressAction.text('Upload in progress..');
    progressBar.css('display', 'block');
    progressBarFill.css('background-color', '#7AB1DF');
    updateProgressBar(0);

    toggleControlBarButtons(true);
}

function onProgress(id, name, uploadedBytes, totalBytes) {
    var amount = Math.ceil((uploadedBytes / totalBytes) * 100);
    updateProgressBar(amount, humanFileSize(totalBytes - uploadedBytes, false) + ' left');
}

function onError(id, name, error, xhr) {
    updateProgressBar(100);
    progressBarFill.css('background-color', "rgb(214, 62, 62)");
    progressAction.text('Upload Failed');
    toggleControlBar(false);
    showNotice('Error during uploading: ' + error, 'error');
}

function onComplete(id, fileName, response) {
    if (!response.success) {
        onError(id, fileName, response.response);
    } else {
        updateProgressBar(100);
        progressBarFill.css('background-color', "#B7D26B");
        progressAction.text('Upload completed!');
        toggleControlBar(false);
        showNotice('Upload Completed! <a href=?page=bb-library&mediaclipId=' + mediaclipId + '>Go to Mediaclip</a>', 'success');
    }
}

function onCancel(uploadIdentifier, fn) {
    showNotice('Upload Cancelled', 'warning', 3000);
    progressAction.text('Upload Cancelled!');
}

function togglePause() {
    if (!uploader) return;
    var state = uploader.getInProgress() > 0;

    if (state) {
        uploader.pauseUpload(currentFileId);
        pauseButton.text('Continue Upload');
    } else {
        uploader.continueUpload(currentFileId);
        pauseButton.text('Pause Upload');
    }
    pauseButton.toggleClass('active', state);
}

function cancelUpload() {
    if (!uploader) return;

    uploader.cancel(currentFileId);
    resetPage();
}

function toggleControlBar(state) {
    controlWrapper.css('display', state ? 'block' : 'none');
}

function toggleControlBarButtons(state) {
    controlWrapper.children('button').css('display', state ? 'block' : 'none');
}

function updateProgressBar(percentage, byteString) {
    progressBarFill.css('width', percentage + "%");
    if (byteString && byteString.length > 0 && byteString.substring(0, 1) != '0')
        progressBarAmount.text(percentage + "% - " + byteString);
    else
        progressBarAmount.text(percentage + "%");
}

function resetPage() {
    //Reset styling & text
    progressBar.css('display', 'none');
    progressWrapper.css('display', 'none');
    progressBarFill.css('background-color', "#B7D26B");
    progressAction.text('Upload completed!');
    progressBarAmount.text('');
}

function humanFileSize(bytes, si) {
    var thresh = si ? 1000 : 1024;
    if (Math.abs(bytes) < thresh) {
        return bytes + ' B';
    }
    var units = si ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while (Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(1) + ' ' + units[u];
}

function showNotice(message, type, duration = 0) {
    noticeDisplay.html(message + '<span>close</span>');
    noticeDisplay.css('display', 'block');

    switch (type) {
        case 'error':
            noticeDisplay.css('border-color', 'red');
            break;
        case 'warning':
            noticeDisplay.css('border-color', 'yellow');
            break;
        case 'notice':
            noticeDisplay.css('border-color', 'grey');
            break;
        case 'success':
            noticeDisplay.css('border-color', 'green');
            break;
    }

    //Update event listener because we have a new instance of the close button
    noticeDisplay.children('span').last().click(hideNotice);

    if (duration > 0) {
        setTimeout(function () {
            noticeDisplay.text('');
            noticeDisplay.css('display', 'none');
        }, duration);
    }
}

function hideNotice() {
    noticeDisplay.css('display', 'none');
}