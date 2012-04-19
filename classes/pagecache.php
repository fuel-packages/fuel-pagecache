<?php

namespace Xvp;

class Pagecache
{
	/**
	 * Is cache enabled?
	 * @var boolean
	 */
    protected $_cache_enabled = false;

    /**
     * Request object
     * @var \Request
     */
    protected $_request  = null;

    /**
     * Response object
     * @var \Response
     */
    protected $_response = null;

    /**
     * Do we need to minify the cached output?
     * @var boolean
     */
    protected $_minified = null;

    /**
     * Path where we will save our cached files
     * @var string
     */
    protected $_cache_dir = null;

    /**
     * Do we need to output a special label in the cached response?
	 * @var boolean
	 */
    protected $_write_output_status = false;

    /**
     * Set the response object
     * @param \Response $response
     */
    public function setResponse(\Response $response)
    {
        $this->_response = $response;
    }

    /**
     * Set the request object
     * @param \Request $request
     */
    public function setRequest(\Request $request)
    {
        $this->_request = $request;
    }

    /**
     * Enable the minified flag
     */
    public function enableMinified()
    {
        $this->_minified = true;
    }

    /**
     * Enable the cache system
     */
    public function enableCache()
    {
        $this->_cache_enabled = true;
    }

    /**
     * Disable the cache system
     */
    public static function disableCache()
    {
        $this->_cache_enabled = false;
    }

    /**
     * Indicates if the request is cacheable or not
     * @return boolean
     */
    public function isCacheable()
    {
        if ($this->_response->status == 200 && $this->_cache_enabled && count($_GET) == 0 && count($_POST) == 0)
        {
            return true;
        }
        return false;
    }

    /**
     * Cache the response based on the parameter $uri
     * @param string $uri
     */
    public function cache($uri)
    {
        $base = DOCROOT.'cache';

        // try to create the base cache dir
        if (!is_dir($base))
        {
            mkdir($base, 0777);
            chmod($base, 0777);
        }

        
        // create the path using the uri structure
        $paths = array();

        if ($uri)
        {
            $paths = explode('/', $uri);
        }

        
        $path = $base;

        foreach ($paths as $sub)
        {
        	// blank segments are not allowed
            if ($sub != '')
            {
                $path .= "/$sub";

                if (!is_dir($path))
                {
                    mkdir($path, 0777);
                    chmod($path, 0777);
                }
            }
        }

        // Cached file path
        $file = "$path/index.html";

        // do not overwrite
        if (!is_file($file))
        {
            $content = $this->_response->body();

            if ($this->_minified)
            {
                $search  = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s');
                $replace = array('>', '<', '\\1');
                $content = preg_replace($search, $replace, $content);
            }

            if ($this->_write_output_status)
            {
            	$content .= PHP_EOL . '<!-- CACHED: ' . date('Y-m-d H:i:s') . ' -->';
            }

            $fh = fopen($file, 'w+');

            fwrite($fh, $content, strlen($content));

            fclose($fh);

            chmod($file, 0777);
        }
    }
}