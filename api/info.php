<?php

class Info
{
    public function __construct(
        private string $token = $_ENV['NOTION_TOKEN'] ?? '',
        private string $databaseId = $_ENV['DATABASE_ID'] ?? '',
        private string $method = $_GET['m'] ?? '',
    )
    {
    }

    public function handle()
    {
        var_dump($this->token);
        var_dump($this->databaseId);
        var_dump($this->method);
    }
}

print_r((new Info())->handle());
