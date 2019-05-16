<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Jeager_http_client
{
    protected $curl;

    protected $url;

    protected $port;

    protected $method;

    protected $param;

    protected $header;

    protected $ssl;

    protected $error;

    protected $info;

    public function __construct()
    {
        $this->init();
    }

    protected function _genUrl()
    {
        if ('GET' !== $this->method)
        {
            return $this->url;
        }

        if (! $this->param)
        {
            return $this->url;
        }

        $Url = explode('?', $this->url);
        $Url = $Url[0];
        $Url .= ('?' . http_build_query($this->param));

        return $Url;
    }

    protected function _setMethod()
    {
        if ('GET' === $this->method)
        {
            return curl_setopt($this->curl, CURLOPT_HTTPGET,TRUE);
        }
        else if ('POST' === $this->method)
        {
            return curl_setopt($this->curl, CURLOPT_POST,TRUE);
        }
        else
        {
            return curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->method);
        }
    }

    protected function _setError()
    {
        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        $this->error = $error . '[' . $errno . ']';
    }

    public function init()
    {
        $this->curl = curl_init();
        $this->port = 80;
        $this->method = 'GET';
        $this->header = [];
        $this->param = [];
        $this->ssl = TRUE;
        $this->url = NULL;
        $this->error = NULL;
        $this->info = NULL;

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        return $this;
    }

    public function Url($url)
    {
        $this->url = trim(strval($url));
        return $this;
    }

    public function Port($port)
    {
        $this->port = intval($port);
        return $this;
    }

    public function GET()
    {
        $this->method = 'GET';
        return $this;
    }

    public function POST()
    {
        $this->method = 'POST';
        return $this;
    }

    public function PUT()
    {
        $this->method = 'PUT';
        return $this;
    }

    public function DELETE()
    {
        $this->method = 'DELETE';
        return $this;
    }

    public function PATCH()
    {
        $this->method = 'PATCH';
        return $this;
    }

    public function Param($key, $value)
    {
        $key = trim(strval($key));
        $value = trim(strval($value));

        if (empty($key) OR empty($value))
        {
            return $this;
        }

        $this->param[$key] = $value;
        return $this;
    }

    public function Params($param)
    {
        if (is_array($param))
        {
            $this->param = $param;
        }
        return $this;
    }

    public function noSSL()
    {
        $this->ssl = FALSE;
        return $this;
    }

    public function Header($header)
    {
        $header = trim(strval($header));

        if (empty($header))
        {
            return $this;
        }

        $this->header[] = $header;
        return $this;
    }

    public function Headers($header)
    {
        if (is_array($header))
        {
            $this->header = $header;
        }
        return $this;
    }

    public function getLastError()
    {
        return $this->error;
    }

    public function Call($jeager = TRUE)
    {
        $url = $this->_genUrl();
        if (! $url)
        {
            $this->error = 'Url不合法！';
            return FALSE;
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);

        if ($this->port)
        {
            curl_setopt($this->curl, CURLOPT_PORT, $this->port);
        }

        $this->_setMethod();

        if (! $this->ssl)
        {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        if ($this->param AND 'GET' !== $this->method)
        {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->param);
        }

        $JeagerTracer = & load_class('JeagerTracer', 'libraries', '');
        $inject = $JeagerTracer->getInject();
        if ($inject AND $jeager)
        {
            $h = ['UBER-TRACE-ID: ' . $inject['UBER-TRACE-ID'], 'UBERCTX-VERSION: '. $inject['UBERCTX-VERSION']];
            $this->header = array_merge($this->header, $h);
        }

        if ($this->header)
        {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->header);
        }

        $output = curl_exec($this->curl);

        if (FALSE === $output)
        {
            $this->_setError();
        }

        $this->info = curl_getinfo($this->curl);

        curl_close($this->curl);

        if (! $jeager)
        {
            $this->_injectJeagerSpan($this->error);
        }

        return $output;
    }

    protected function _injectJeagerSpan($error = NULL)
    {
        $u = parse_url($this->url);
        if (! isset($u['path']))
        {
            $u['path'] = '/';
        }

        $JeagerSpan = & load_class('JeagerSpan', 'libraries', '');
        $JeagerSpan->init()
                   ->setOperationName('HttpClient - ' . $this->method)
                   ->setRpcTag('client', $this->url, $u['host'], $this->port, $u['path']);

        if ($error)
        {
            $JeagerSpan->setMessage($this->error)->setError();
        }
        else
        {
            $JeagerSpan->setMessage('Done.');
        }

        $JeagerSpan->inject();
    }
}