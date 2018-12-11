Blox.ajax = function(src, dst, afterFunction) {
    var x=window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    x.onreadystatechange=function() {
        if(x.readyState==4&&x.status==200) {
        	if (!dst) {
				if (/src=(\d+)/.test(src))
					dst = RegExp.$1;
				else if (/block=(\d+)/.test(src))
					dst = RegExp.$1;
            }

			if (dst) {
				if (/^[0-9]+$/.test(dst))//  is a number	//if (isNaN(dst)) //  is not a number
					dst = "blox-dst-"+dst; // for the regular block
                document.getElementById(dst).innerHTML=x.responseText;                    
            }

            if(afterFunction) {
                var c = new Function(afterFunction);
                c();
            }
        }
    };

    //src = encodeURI(src); // For IE8
    x.open('GET', src, true);
    x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');// ajax detect
    x.send(null);
}