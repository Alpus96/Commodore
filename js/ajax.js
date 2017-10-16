class Ajax {
    /**
    *   @description    A function to send ajax requests with json data to the server.
    *
    *   @param			url: 		'/example', a string containing the url where to send
    *                               the post request.
    *                   data: 		{username: "", password: ""}, an object containing
    *                               the information to send to the server.
    *                   callback: 	(error, result) => {...}, a function to call when done.
	**/
	post (url, data, callback) {
		const request_data = JSON.stringify(data);
		const request = new XMLHttpRequest();
		request.open('POST', url, true);	//  Pass the input url parameter.
		request.responseType = 'json';
		request.setRequestHeader('Content-Type', 'Application/JSON');
		//	Handle when the request succeds.
		request.onload = () => {
			//  Returns the result as result through the callback function.
			if (request.status < 400) {
				callback(null, request.response);
			} else {
				callback(true, request.status)
			}
		};
		//	Handle error response on request.
		request.onerror = () => {
			//  Log and return the error through the callback.
			const msg = request.status + ':' + request.statusText;
			//console.error(msg);
			callback(true, msg);
		};
		//  Pass the input data parameter converted to JSON when sending the request.
		request.send(request_data);
	}

    /**
    *       @description 	A function to send get requests to the server.
    *
    *       @param 			url: 		'/example', a string containing the relative url for
    *                                    where to send the requets.
    *						callback: 	(error, response) => {}, a function hadling the
    *                                   response or error from the request.
    **/
    get (url, callback) {
        const request = new XMLHttpRequest();
        request.open('GET', url, true);	//  Pass the input url parameter.
        request.responseType = 'json';
		//	Handle when the request succeds.
        request.onload = () => {
            callback(false, request.response);
        };
		//	Handle error response on request.
        request.onerror = () => {
            console.error(request.status, ':', request.statusText);
            callback(true, request.status);
        };
		//	Send the request.
		request.send();
    }

}

const AJAX = new Ajax();
