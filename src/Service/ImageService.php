<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageService
{
    public function __construct(private string $uploadsDirectory, private string $uploadsBaseUrl) {

    }

    /**
     * @param UploadedFile $imageFile
     * @return string Uploaded Image URL
     */
    public function saveImage(UploadedFile $imageFile): string
    {
        $newFilename = uniqid() . '.' . $imageFile->guessExtension();

        $targetDirectory = $this->uploadsDirectory;
        $imageFilePath = $targetDirectory . '/' . $newFilename;

        $imageFile->move($targetDirectory, $newFilename);

        $this->resizeImage($imageFilePath, 1280, 1280);

        return $this->uploadsBaseUrl . '/' . $newFilename;
    }

    public function resizeImage(string $filePath, int $maxWidth, int $maxHeight): void
    {
        [$originalWidth, $originalHeight, $imageType] = getimagesize($filePath);

        $scale = min($maxWidth / $originalWidth, $maxHeight / $originalHeight, 1);
        $newWidth = (int)($originalWidth * $scale);
        $newHeight = (int)($originalHeight * $scale);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($filePath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($filePath);
                break;
            default:
                throw new \InvalidArgumentException('Unsupported image type');
        }

        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $filePath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $filePath);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($newImage);
    }
}
