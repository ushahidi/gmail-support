<?php

namespace Ushahidi\Gmail\Contracts;

interface TokenStorage
{
    public function get(string $email, string $key = null);

    public function save(array $token);

    public function delete(string $email);
}