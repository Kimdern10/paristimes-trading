<?php

namespace App\Models;

use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A photo of the shop or team, shown on the home and about pages.
 *
 * @property int $id
 * @property string $path
 * @property string $caption
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['path', 'caption', 'sort_order'])]
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the public URL of the large copy.
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Get the public URL of the small copy used on phones and thumbnails.
     */
    public function smallUrl(): string
    {
        return Storage::disk('public')->url($this->smallPath());
    }

    /**
     * Get the path of the small copy on the public disk.
     */
    public function smallPath(): string
    {
        return Str::replaceLast('.webp', '-sm.webp', $this->path);
    }

    /**
     * Delete both image files from the public disk.
     */
    public function deleteFiles(): void
    {
        Storage::disk('public')->delete([$this->path, $this->smallPath()]);
    }
}
