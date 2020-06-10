<?php

namespace Ushahidi\Gmail\Contracts;

interface TokenStorage
{
    public function get($email, $string = null);

    public function save($email, $token);

    public function delete($email);
}