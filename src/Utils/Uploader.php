<?php

namespace App\Utils;

/**
 * A basic upload service class for uploading files via HTML forms.
 */
class Uploader
{
    /**
     * Maximum allowed file size in bytes.
     * @var int
     */
    private $maxFileSize = 2 * 1024 * 1024;

    /**
     * Valid file extension.
     * @var string
     */
    private $validExtension;

    /**
     * Valid media types.
     * @var array
     */
    private $validMediaTypes;

    /**
     * Destination directory.
     * @var string
     */
    private $dir;

    /**
     * Destination file name.
     * @var string
     */
    private $destinationFileName;

    /**
     * Set the maximum allowed file size in bytes.
     */
    public function setMaxFileSize(int $maxFileSize): void
    {
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Set allowed file extension without leading dot. For example, 'tgz'.
     */
    public function setValidExtension(string $validExtension): void
    {
        $this->validExtension = $validExtension;
    }

    /**
     * Set array of valid media types.
     */
    public function setValidMediaTypes(array $validMediaTypes): void
    {
        $this->validMediaTypes = $validMediaTypes;
    }

    /**
     * Set destination directory.
     */
    public function setDir(string $dir): void
    {
        $this->dir = $dir;
    }

    /**
     * Set the destination file name.
     */
    public function setDestinationFileName(string $destinationFileName): void
    {
        $this->destinationFileName = $destinationFileName;
    }

    /**
     * Upload file.
     */
    public function upload(string $key): string
    {
        $files = isset($_FILES[$key]) ? $_FILES[$key] : [];

        // Check if uploaded file size exceeds the ini post_max_size directive.
        if(
            empty($_FILES)
            && empty($_POST)
            && isset($_SERVER['REQUEST_METHOD'])
            && strtolower($_SERVER['REQUEST_METHOD']) === 'post'
        ) {
            $max = ini_get('post_max_size');
            throw new \Exception('Error on upload: Exceeded POST content length server limit of '.$max);
        }

        // Some other upload error happened
        if (empty($files) || $files['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error on upload: Something went wrong. Error code: '.$files['error']);
        }

        // Be sure we're dealing with an upload
        if ($this->isUploadedFile($files['tmp_name']) === false) {
            throw new \Exception('Error on upload: Invalid file definition');
        }

        // Check file extension
        $uploadedName = $files['name'];
        $ext = $this->getFileExtension($uploadedName);
        if (isset($this->validExtension) && $ext !== $this->validExtension) {
            throw new \Exception('Error on upload: Invalid file extension. Should be .'.$this->validExtension);
        }

        // Check file size
        if ($files['size'] > $this->maxFileSize) {
            throw new \Exception('Error on upload: Exceeded file size limit '.$this->maxFileSize.' bytes');
        }

        // Check zero length file size
        if (!$files['size']) {
            throw new \Exception('Error on upload: Zero-length patches are not allowed');
        }

        // Check media type
        $type = $this->getMediaType($files['tmp_name']);
        if (isset($this->validMediaTypes) && !in_array($type, $this->validMediaTypes)) {
            throw new \Exception('Error: Uploaded patch file must be text file
                (save as e.g. "patch.txt" or "package.diff")
                (detected "'.htmlspecialchars($type, ENT_QUOTES).'")'
            );
        }

        // Rename the uploaded file
        $destination = $this->dir.'/'.$this->destinationFileName;

        // Move uploaded file to final destination
        if (!$this->moveUploadedFile($files['tmp_name'], $destination)) {
            throw new \Exception('Error on upload: Something went wrong');
        }

        return $destination;
    }

    /**
     * Checks if given file has been uploaded via POST method. This is wrapped
     * into a separate method for convenience of testing it via phpunit and using
     * a mock.
     */
    protected function isUploadedFile(string $file): bool
    {
        return is_uploaded_file($file);
    }

    /**
     * Move uploaded file to destination. This method is wrapping PHP function
     * to allow testing with PHPUnit and creating a mock object.
     */
    protected function moveUploadedFile(string $source, string $destination): bool
    {
        return move_uploaded_file($source, $destination);
    }

    /**
     * Rename file to a unique name.
     */
    protected function renameFile(string $filename): ?string
    {
        $ext = $this->getFileExtension($filename);

        $rand = uniqid(rand());

        $i = 0;
        while (true) {
            $newName = $rand.$i.'.'.$ext;

            if (!file_exists($this->dir.'/'.$newName)) {
                return $newName;
            }

            $i++;
        }
    }

    /**
     * Returns file extension without a leading dot.
     */
    protected function getFileExtension(string $filename): string
    {
        return strtolower(substr($filename, strripos($filename, '.') + 1));
    }

    /**
     * Guess file media type (formerly known as MIME type) using the fileinfo
     * extension. If fileinfo extension is not installed fallback to plain text
     * type.
     */
    protected function getMediaType(string $file): string
    {
        // If fileinfo extension is not available it defaults to text/plain.
        if (!class_exists('finfo')) {
            return 'text/plain';
        }

        $finfo = new \finfo(FILEINFO_MIME);

        if (!$finfo) {
            throw new \Exception('Error: Opening fileinfo database failed.');
        }

        // Get type for a specific file
        $type = $finfo->file($file);

        // Remove the charset part
        $mediaType = explode(';', $type);

        return isset($mediaType[0]) ? $mediaType[0] : 'text/plain';
    }
}
