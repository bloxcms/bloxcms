<?php

class Url
{

/** 
    Recommendations for variable naming
    $url    Absolute link (although, as the input data can be used in any form: parametric or human-readable, absolute or relative to the main page). Example: http://sites.ru/shop/
    $path   Absolute path from domain root (root-relative). That is, it starts with a slash.  Example: / shop/
    $href   Relative URL when it is not known what it is: $phref or $hhref.
    $phref  Relative parametric URL.   Example: ?page=3
    $hhref  Relative human-readable URL.   Example: shop/
*/





    # Convert the array into a parameter query. The dimension of arrays is unlimited
    public static function arrayToQuery($arr)
    {
        return urldecode(http_build_query($arr));
        /* See also            
            function append_params($array, $parent='')
            in http://php.net/parse_str
        */
    }



    /**
     * @return string Absolute URL
     * @param string $url URL in any form
     * @param string $baseUrl Absolute URL of a starting page (with trailing slash)
     */
    public static function convertToAbsolute($url, $baseUrl=null) # Do not forget to remove the slash at the end
    {
        # Already absolute URL
        if ('' != parse_url($url, PHP_URL_SCHEME) || '//' == substr($url, 0, 2))
            return $url;
        if (empty($baseUrl))
            $baseUrl = Blox::info('site','url').'/';
        extract(parse_url($baseUrl)); # parse base URL and convert to variables: $scheme, $host, $path
        # queries and anchors
        if ($url[0]=='#' || $url[0]=='?')
            return $baseUrl.$url;
        //$path = preg_replace('#/[^/]*$#u', '', $path); //# remove non-directory element from path
        # destroy path if relative url points to root
        if ($url[0] == '/')
            $path = '';
        # dirty absolute URL
        $absUrl = "$host$path/$url";
        # replace '//' or '/./' or '/foo/../' with '/'
        for (
            $n=1; 
            $n>0; 
            $absUrl = preg_replace(['#(/\.?/)#u', '#/(?!\.\.)[^/]+/\.\./#u'], '/', $absUrl, -1, $n)
        ) {}        
        # absolute URL is ready
        return ($scheme ? $scheme.':' : '').'//'.$absUrl;
    }



    /**
     * @return string Relative URL or false
     * @param string $url URL in any form
     * @param string $baseUrl Absolute URL of a starting page (with trailing slash). It may be without trailing slash, but the last element must be a folder
     * @example
     *     $url = 'http://username:password@hostname/path?query=value#fragment';
     *     $url = '//www.host.ru/path/';
     *     $url = '/path/';
     */
    public static function convertToRelative($url, $baseUrl=null)
    {
        # queries and anchors
        if ($url[0]=='#' || $url[0]=='?')
            return $url;
        if (empty($baseUrl))
            $baseUrl = Blox::info('site','url');
        $baseComps = self::getAbsUrlComponents($baseUrl);
        if (substr($baseComps['path'], -1) == '/')
            $baseComps['path'] = substr_replace($baseComps['path'], '', -1) ;   # Removes '/' in the end
        $urlComps = self::getAbsUrlComponents($url);
        if (empty($urlComps['host']) && $url[0] !='/')
            return $url;
        # There may be schemes: 'http', 'https', '' (i.e. //apple.com)
        if ($baseComps['scheme'] && $urlComps['scheme'])
            if ($baseComps['scheme'] != $urlComps['scheme'])
                return false;
        if ($baseComps['host'] && $urlComps['host'])
            if ($baseComps['host'] != $urlComps['host'])
                return false;
        $basePathLen = strlen($baseComps['path']);
        if ($baseComps['path'] != substr($urlComps['path'], 0, $basePathLen))
            return false;
        $rurl = substr($urlComps['path'], $basePathLen+1);
        if ($urlComps['query'])
            $rurl .= '?'.$urlComps['query'];
        if ($urlComps['fragment'])
            $rurl .= '#'.$urlComps['fragment'];
        return $rurl;
        # parse_url:scheme(http), host, port, user, pass, path, query (after the question mark ?), fragment(after the hashmark #)
        # To extract only one component, use the flag: PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH, PHP_URL_QUERY or PHP_URL_FRAGMENT
    }



    # TODO: Variable Declaration $pagehrefQuery files in the scripts folder to make it automatically. $pagehrefQuery = '&pagehref='.Blox:: getPageHref(true);
    
    # Encoding based on base64_encode () function with additional replacement of characters prohibited in URL: + / =
    # Is used to pass multiple redirects of another URL to a URL as a parameter.
    # At the end of the transmission chain, this parameter must be decoded using Url::decode().
    # The urlencode() function will not work for this purpose because the passed parameter is automatically decoded.
	public static function decode($str)
    {
        if (empty($str))
            return $str;
        
        if (preg_match('~[A-Za-z0-9_-]~', $str)) { # Check whether coded by Url::encode
            return base64_decode(
                str_pad(
                    strtr($str, '-_', '+/'), 
                    strlen($str) % 4, 
                    '='//, STR_PAD_RIGHT
                )
            );
        }
        else {
            Blox::prompt(sprintf($terms['url-decode-error'], $str), true);
            return $str;
        }   
    }
	public static function encode($str)
    {
        return rtrim(
            strtr(
                base64_encode($str), 
                '+/', 
                '-_'
            ), 
            '='
        );
    }


	public static function exists($url)
    {
        $exists = function($url) {
            $result = false;
            if ($headers = @get_headers($url)) {
                if (strpos($headers[0], '200 OK') !== false) {
                    unset($headers[0]);
                    foreach ($headers as $h) { # Check for Invalid file
                        if (strpos($h, 'Content-Length:') !== false) {
                            if (strpos($h, 'Content-Length: 0') === false) 
                                $result = true;
                            break;
                        }
                    }
                } // elseif (strpos($headers[0], '404 Not Found') !== false) {;}
            }
            return $result;
        };
        #
        if (substr($url, 0, 2) === '//') { # Check for URL without scheme
            if ($exists('http:'.$url))
                return true;
            elseif ($exists('https:'.$url))
                return true;
            else
                return false;
        } elseif ($exists($url))
            return true;
        else
            return false;
        /** 
        $headers
        OK
            [0] => HTTP/1.1 200 OK
            [1] => Date: Mon, 25 Dec 2017 11:27:59 GMT
            [2] => Server: Apache
            [3] => Last-Modified: Wed, 13 Dec 2017 17:07:23 GMT
            [4] => ETag: "2b3c3-5603bcd0daac9"
            [5] => Accept-Ranges: bytes
            [6] => Content-Length: 177091
            [7] => Connection: close
            [8] => Content-Type: image/jpeg
        Not exists
            [0] => HTTP/1.1 404 Not Found
            [1] => Date: Mon, 25 Dec 2017 11:29:39 GMT
            [2] => Server: Apache
            [3] => Content-Length: 2931
            [4] => Connection: close
            [5] => Content-Type: text/html;charset=UTF-8
        Invalid image file
            [0] => HTTP/1.1 200 OK
            [1] => Server: nginx
            [2] => Date: Mon, 25 Dec 2017 11:26:24 GMT
            [3] => Content-Type: image/jpeg
            [4] => Content-Length: 0
            [5] => Last-Modified: Fri, 13 Nov 2015 06:01:07 GMT
            [6] => Connection: close
            [7] => ETag: "56457ca3-0"
            [8] => Expires: Thu, 31 Dec 2037 23:55:55 GMT
            [9] => Cache-Control: max-age=315360000
            [10] => Strict-Transport-Security: max-age=15768000
            [11] => Accept-Ranges: bytes
        */

        /* Variant 2 by curl
        $urlExists = function($url) {
            if (function_exists('curl_init')) {
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_NOBODY, true);
                $result = curl_exec($curl);
                if ($result !== false) {
                    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
                    if ($statusCode <> 404) 
                        return true;
                }
            }
        };
        if (self::info('user','user-is-admin') && !$urlExists($href))
            self::prompt(sprintf($terms['url-not-exists'], '<b>'.$code.'</b>', $dst), true);
        */
        
        /* Variant 3 by fsockopen
            https://toster.ru/q/228549
            https://www.it-rem.ru/headers-curl-vs-get_headers-vs-fsockopen.html
        */
        /*
        There was a problem with resolving of public DNS 8.8.8.8 (Google) for get_headers() and curl_exec() on the web-server.
        Problem was solved by changing independent providers: 1.1.1.1 (CloudFlare) and 91.239.100.100 (uncensoreddns.org).
        */
    }
    


    /**        
        Like parse_url()
        $url = 'http://username:password@hostname/path?query=value#fragment';
        Return:        
            [scheme] => http
            [host] => hostname
            [user] => username
            [pass] => password
            [path] => /path
            [query] => query=value
            [fragment] => fragment
        The difference is that it processes such URLS:
            without scheme: $url = '//www.host.ru/path/';
            without host: $url = '/path/';
    */
    public static function getAbsUrlComponents($url)
    {
        $comps = parse_url($url);
        if (empty($comps['scheme']) || empty($comps['scheme'])) {
            if ($comps['path'][0] == '/') {
                $path1 = substr($comps['path'], 1);  # delete the initial slash
                if ($path1[0] == '/') { 
                    $path2 = substr($path1, 1);  # delete the second initial slash
                    if ($aa = Str::splitByMark($path2, '/')) {
                        $comps['host'] = $aa[0];
                        $comps['path'] = '/'.$aa[1];
                    } else {
                        $comps['host'] = $path2;
                        $comps['path'] = '';
                    }
                }
            } else
                return false;
        } 
        return $comps;
        /** NEW
        $url = 'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment'; 
        if ($url === unparse_url(parse_url($url))) { 
          print "YES, they match!\n"; 
        } 

        function unparse_url($parsed_url) { 
          $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
          $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
          $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
          $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
          $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
          $pass     = ($user || $pass) ? "$pass@" : ''; 
          $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
          $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
          $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
          return "$scheme$user$pass$host$port$path$query$fragment"; 
        } 
        */
    }
        



    /** 
    * $decoded = Url::punyDecode($encoded);
    * $encoded - desirable domain, but can be and URL   
    */       
	public static function punyDecode($url)
    {
        require_once Blox::info('cms','dir').'/vendor/Misc/idna_convert.class.php';
        $puny = new idna_convert();
        return $puny->decode($url); 
    }
    public static function punyEncode($url)
    {
        if ($url) {
            $url2 =  preg_replace_callback( 
                '~//([^/]*?)/~sU',
                function ($matches) {
                    require_once Blox::info('cms','dir').'/vendor/Misc/idna_convert.class.php';
                    $puny = new idna_convert();
                    $host = $puny->encode($matches[1]);
                    return '//'.$host.'/';
                },
                $url
            );
        }
        return $url2 ?: $url;                 
    }
    

    
    /** 
    * REMAKE with
    *   http://php.net/parse_str
    *   OR
    *   function parse_query_string($url, $qmark=true) in http://php.net/parse_str    
    */
    public static function queryToArray($query, $options=[])
    {
        if ($options)
            Arr::formatOptions($options);
        
        # NOT USED YET, NOT DOCUMENTED
        $options += ['remove-lost'=>false]; # Remove params where there is "=" but no value. Remove Elements with lost values
         
        if ($query[0] == '?')
            $query = substr($query, 1);
        
        $arr = [];
        if ($params = explode('&', $query))
        {
            foreach ($params as $param) //page=2&block=14&p[1]=1&p[2]=0
            {
                if ($param) {
                    $pair = explode('=', $param);
                    if ($pieces = explode('[', $pair[0])) {
                        if ($pair[1]===null)                                 # p
                            $value = '';
                        elseif ($pair[1]==='' && $options['remove-lost'])     # p=       
                            continue;
                        else                                                 # p=0   p=1
                            $value = $pair[1];
                        $pieces = array_reverse($pieces); # The last element is the parameter name
                        foreach ($pieces as $k)
                        {
                            $ar = [];
                            $k = str_replace(']', '', $k);
                             # The separator may not be '&', but '&amp;.'. Remove 'amp;'
                            if (substr($k, 0, 4) == 'amp;')                        
                                $k = substr($k, 4);
                            $ar[$k] = $value;
                            $value = $ar;
                        }
                        $arr = Arr::mergeByKey($arr, $ar);
                    }
                }
            }
            return $arr;
        }
    }




      
        
        

    /**
     * replacements - regular [pattern=>replacement, ...]
     * 
     */
    public static function redirect($url, $options=[])
    {
        if ($options)
            Arr::formatOptions($options);
        
        # Defaults
        $options += ['exit'=>false, 'status'=>301, 'replacements'=>[], 'loop-protection'=>false, 'puny-encode'=>false];
        if ($options['replacements']) {
            foreach ($options['replacements'] as $pattern => $replacement)
                $url = preg_replace($pattern, $replacement, $url);
        }
        # cookies off
        if (Blox::info('user','id') && SID) {
            $amp = ($url == '?') ? '' : '&';
            $url .= $amp.SID;
        }

        # To Absolute Url
        if ($url[0] == '?')
            $url = Blox::info('site','url').'/'.$url;
        else
            $url = self::convertToAbsolute($url);
        #
        if ($options['loop-protection']) {
            if ($url == $_SERVER['HTTP_REFERER']) # abs 
                return false;
            elseif (self::convertToAbsolute($url) == $_SERVER['HTTP_REFERER']) # if $url turned out to be relative
                return false;
        }

        if ($options['puny-encode'])
            $url = self::punyEncode($url);
        http_response_code($options['status']);
        header('Location: '.$url);
        if ($options['exit'])
            exit();
        else
            return true;
    }




    # pagehref is re-encoded as HTTP_REFERER goes in decoded form
	public static function redirectToReferrer($options=[])
    {
        self::redirect($_SERVER['HTTP_REFERER'], $options);
    }


    # DEPRECATE?
    # Prepare the URL entered by the user for storage in the database. To remove the schema(the Protocol) if it is http
    public static function removeHttpFromUrl($str)
    {
        $str = trim($str);
        $scheme = mb_strtolower(parse_url($str, PHP_URL_SCHEME));
        if ('http'== $scheme)
            $str = preg_replace('#^https?://#iu', '', $str);# remove non-directory element from path
        return $str;
    }
    # Prepare the URL extracted from the database to the output in the anchor. Add http schema if the schema was not
    public static function addHttpToUrl($str)
    {
        $scheme = parse_url($str, PHP_URL_SCHEME);
        if (empty($scheme))
            $str = 'http://'.$str;
        return $str;
    }


}