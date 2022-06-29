<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Files;

class RemoteTemporaryFile extends TemporaryFile
{
    /**
     * @var string
     */
    private $disk;

    /**
     * @var null|Disk
     */
    private $diskInstance;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var LocalTemporaryFile
     */
    private $localTemporaryFile;

    public function __construct(string $disk, string $filename, LocalTemporaryFile $localTemporaryFile)
    {
        $this->disk = $disk;
        $this->filename = $filename;
        $this->localTemporaryFile = $localTemporaryFile;

        $this->disk()->touch($filename);
    }

    public function __sleep()
    {
        return ['disk', 'filename', 'localTemporaryFile'];
    }

    public function getLocalPath(): string
    {
        return $this->localTemporaryFile->getLocalPath();
    }

    public function existsLocally(): bool
    {
        return $this->localTemporaryFile->exists();
    }

    public function exists(): bool
    {
        return $this->disk()->exists($this->filename);
    }

    public function deleteLocalCopy(): bool
    {
        return $this->localTemporaryFile->delete();
    }

    public function delete(): bool
    {
        // we don't need to delete local copy as it's deleted at end of each chunk
        if (! config('excel.temporary_files.force_resync_remote')) {
            $this->deleteLocalCopy();
        }

        return $this->disk()->delete($this->filename);
    }

    public function sync(): TemporaryFile
    {
        if (! $this->localTemporaryFile->exists()) {
            touch($this->localTemporaryFile->getLocalPath());
        }

        $this->disk()->copy(
            $this,
            $this->localTemporaryFile->getLocalPath()
        );

        return $this;
    }

    /**
     * Store on remote disk.
     */
    public function updateRemote()
    {
        $this->disk()->copy(
            $this->localTemporaryFile,
            $this->filename
        );
    }

    /**
     * @return resource
     */
    public function readStream()
    {
        return $this->disk()->readStream($this->filename);
    }

    public function contents(): string
    {
        return $this->disk()->get($this->filename);
    }

    /**
     * @param resource|string $contents
     */
    public function put($contents)
    {
        $this->disk()->put($this->filename, $contents);
    }

    public function disk(): Disk
    {
        return $this->diskInstance ?: $this->diskInstance = app(Filesystem::class)->disk($this->disk);
    }
}
