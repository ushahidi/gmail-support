<?php

namespace Ushahidi\Gmail;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Ushahidi\Gmail\Contracts\TokenStorage;

class TokenDiskStorage implements TokenStorage
{
    protected $allow_json_encrypt = true;

    protected $disk;

    protected $user;

    public function __construct()
    {
        $this->disk = Storage::disk('local');
    }

    public function get($string = null)
    {
        $file = $this->getFile();

        if ($this->disk->exists($file)) {
            $token = $this->getTokenFromFile($file);

            if ($string) {
                if (isset($token[$string])) {
                    return $token[$string];
                }
            } else {
                return $token;
            }
        }

        return null;
    }

    public function save($token)
    {
        $file = $this->getFile();

        if ($this->disk->exists($file)) {
            if (empty($token['email'])) {
                $savedConfigToken = $this->getTokenFromFile($file);

                if (isset($savedConfigToken['email'])) {
                    $token['email'] = $savedConfigToken['email'];
                }
            }

            $this->disk->delete($file);
        }

        $this->saveTokenToFile($token, $file);
    }

    public function delete()
    {
        $file = $this->getFile();

        if ($this->disk->exists($file)) {
            $this->disk->delete($file);
        }

        $this->saveTokenToFile($file, []);
    }

    protected function getTokenFromFile($file)
    {
        $allowJsonEncrypt = $this->allow_json_encrypt;

        try {
            $token = json_decode($allowJsonEncrypt ?
                decrypt($this->disk->get($file)) :
                $this->disk->get($file), true);
        } catch (FileNotFoundException $e) {
            $token = [];
        }

        return $token;
    }

    protected function saveTokenToFile($token, $file)
    {
        $allowJsonEncrypt = $this->allow_json_encrypt;

        $allowJsonEncrypt ?
            $this->disk->put($file, encrypt(json_encode($token))) :
            $this->disk->put($file, json_encode($token));
    }

    private function getFile()
    {
        $fileName = $this->getFileName();
        return "gmail/tokens/$fileName.json";
    }

    private function getFileName()
    {
        return $this->user;
    }
}