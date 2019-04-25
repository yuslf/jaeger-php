<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class JeagerHook {

    public $JeagerTracer;

    public function __construct()
    {
        $this->JeagerTracer = & load_class('JeagerTracer', 'libraries', '');
    }

    public function Before()
    {
        $this->JeagerTracer->injectApiSpan();
    }

    public function After()
    {
        $this->JeagerTracer->flush();
    }
}
