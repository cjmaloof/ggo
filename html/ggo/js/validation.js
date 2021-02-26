// Returns a Promise resolving to true if no errors, false otherwise
function ajaxValidate(url, requestData, errorDiv) {
    return reqwest({
        url: url,
        method: 'get',
        type: 'text/html',
        data: requestData
    }).then(function(response) {
        var errorHtml = response.responseText;
        errorDiv.innerHTML = errorHtml;
        return errorHtml == "";
    });
}