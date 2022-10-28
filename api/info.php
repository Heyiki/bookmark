<?php

class Info
{
    private string $method;
    public function __construct() {
        $this->method = $_GET['m'] ?? '';
    }

    public function handle()
    {
        var_dump($this->method);
    }
}

print_r((new Info())->handle());
