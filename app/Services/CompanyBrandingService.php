<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CompanyBrandingService
{
    public function storeImage(Company $company, UploadedFile $file, string $kind): string
    {
        $dir = public_path('uploads/companies/'.$company->id);
        File::ensureDirectoryExists($dir, 0755, true);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        $filename = $kind.'_'.Str::lower(Str::random(8)).'.'.$ext;
        $file->move($dir, $filename);

        return 'uploads/companies/'.$company->id.'/'.$filename;
    }

    public function deleteIfExists(?string $relative): void
    {
        if (! filled($relative)) {
            return;
        }

        $path = public_path(ltrim($relative, '/'));
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
