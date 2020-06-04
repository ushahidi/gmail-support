<?php


namespace Ushahidi\Gmail\Contracts;


interface TokenStorage
{
    public function get($string = null);

    public function save($token);

    public function delete();
}