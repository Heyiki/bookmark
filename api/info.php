<?php

class Info
{
    public function __construct(
        public string $token = $_ENV['NOTION_TOKEN'] ?? '',
        public string $method = $_GET['m'] ?? '',
  ) {}

    public function handle()
    {
        var_dump($this->token);
        var_dump($this->method);
    }
}

print_r((new Info())->handle());
