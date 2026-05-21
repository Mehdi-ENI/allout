<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{

    public function upload(UploadedFile $file, string $directory, $namePrefix = '')
    {
        $newFileName = ($namePrefix ? $namePrefix . "-" : '') . uniqid() . "." . $file->guessExtension();
        $file->move($directory, $newFileName);
        return $newFileName;
    }

    public function delete(string $filename, string $directory)
    {
        unlink($directory . DIRECTORY_SEPARATOR . $filename);
    }

    public function update(string $oldFilename, string $directory, UploadedFile $file, $namePrefix = ''){
        $this->delete($oldFilename, $directory);
        return $this->upload($file, $directory, $namePrefix);
    }
}
