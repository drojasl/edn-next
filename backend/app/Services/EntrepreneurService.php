<?php

namespace App\Services;

use App\Models\Entrepreneur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EntrepreneurService
{
    /**
     * Create a new entrepreneur.
     */
    public function create(array $data): Entrepreneur
    {
        $socialLinks = $data['social_links'] ?? [];
        unset($data['social_links']);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;

        $entrepreneur = Entrepreneur::create($data);

        if (!empty($socialLinks)) {
            foreach ($socialLinks as $link) {
                $entrepreneur->socialLinks()->create($link);
            }
        }

        return $entrepreneur;
    }

    /**
     * Update an existing entrepreneur.
     */
    public function update(Entrepreneur $entrepreneur, array $data, $profilePictureFile = null): Entrepreneur
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($profilePictureFile) {
            // Delete old picture if it exists
            if ($entrepreneur->profile_picture) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $entrepreneur->profile_picture));
            }
            // Store new picture
            $path = $profilePictureFile->store('profiles', 'public');
            $data['profile_picture'] = '/storage/' . $path;
        }

        $socialLinks = $data['social_links'] ?? null;
        unset($data['social_links']);

        $entrepreneur->update($data);

        if ($socialLinks !== null) {
            $entrepreneur->socialLinks()->delete();
            foreach ($socialLinks as $link) {
                $entrepreneur->socialLinks()->create($link);
            }
        }

        return $entrepreneur;
    }

    /**
     * Delete an entrepreneur.
     */
    public function delete(Entrepreneur $entrepreneur): void
    {
        // Note: The model uses SoftDeletes according to docs
        $entrepreneur->delete();
    }
}
