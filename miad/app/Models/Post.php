<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $connection = 'farsi_fahr2';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'author_name',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($post) {
            if ($post->isDirty('image') && $post->image && !str_ends_with($post->image, '.webp')) {
                $fullPath = storage_path('app/public/' . $post->image);
                
                if (file_exists($fullPath)) {
                    $info = @getimagesize($fullPath);
                    if (!$info) return;
                    
                    $mime = $info['mime'];
                    $image = null;

                    switch ($mime) {
                        case 'image/jpeg':
                            $image = @imagecreatefromjpeg($fullPath);
                            break;
                        case 'image/png':
                            $image = @imagecreatefrompng($fullPath);
                            if ($image) {
                                imagepalettetotruecolor($image);
                                imagealphablending($image, true);
                                imagesavealpha($image, true);
                            }
                            break;
                        case 'image/gif':
                            $image = @imagecreatefromgif($fullPath);
                            break;
                    }

                    if ($image) {
                        $dirname = pathinfo($post->image, PATHINFO_DIRNAME);
                        $filename = pathinfo($post->image, PATHINFO_FILENAME);
                        $newRelativePath = ($dirname === '.' ? '' : $dirname . '/') . $filename . '.webp';
                        $newFullPath = storage_path('app/public/' . $newRelativePath);

                        // Convert to webp with 85% quality
                        if (imagewebp($image, $newFullPath, 85)) {
                            imagedestroy($image);
                            // Set permissions to 777 to avoid access issues
                            @chmod($newFullPath, 0777);
                            
                            // Delete original file if it's different
                            if ($fullPath !== $newFullPath) {
                                @unlink($fullPath);
                            }
                            // Update model attribute
                            $post->image = $newRelativePath;
                        } else {
                            @imagedestroy($image);
                        }
                    }
                }
            }
        });
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
