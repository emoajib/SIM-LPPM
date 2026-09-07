<?php

namespace App\Support\MediaLibrary;

use App\Models\User;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    protected function getBasePath(Media $media): string
    {
        $collection = $media->collection_name;
        $model = $media->model;

        $user = null;

        // Identifikasi User dari Model
        if ($model instanceof User) {
            $user = $model;
        } elseif (method_exists($model, 'submitter')) {
            /** @var User|null $user */
            // property access is intentional: Laravel relationship
            // @phpstan-ignore-next-line
            $user = $model->submitter;

            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            // ProgressReport::submitter() uses submitted_by which is null in draft state.
            // Fall back to the proposal's submitter (always set) for a consistent path
            // that is identical whether the file is uploaded in draft or submitted state.
            if (! $user && method_exists($model, 'proposal')) {
                // @phpstan-ignore-next-line
                $user = $model->proposal?->submitter;
            }
        } elseif (method_exists($model, 'user')) {
            /** @var User|null $user */
            // @phpstan-ignore-next-line
            $user = $model->user;
        }

        if ($user) {
            // Cek identity_id (NIDN/NIK) dari relasi identity
            // Kita coba muat relasi jika belum ada
            if (! $user->relationLoaded('identity')) {
                $user->load('identity');
            }

            $identityId = $user->identity->identity_id ?? 'no-id';
            $userName = Str::slug($user->name);
            $userFolder = "{$identityId}-{$userName}";
        } else {
            // Fallback jika tidak ada konteks user
            $modelName = Str::slug(class_basename($media->model_type));
            $modelId = is_string($media->model_id) ? substr($media->model_id, 0, 8) : $media->model_id;
            $userFolder = "{$modelName}-{$modelId}";
        }

        return "{$collection}/{$userFolder}/{$media->id}";
    }
}
