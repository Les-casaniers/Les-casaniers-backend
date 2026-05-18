<?php

namespace App\Services\Guides;

use App\Models\Guide;
use App\Repositories\Guides\GuideRepositoryInterface;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuideService
{
    protected string $imagePath;
    protected string $imageUrlBase;

    public function __construct(protected GuideRepositoryInterface $guideRepository)
    {
        $this->imagePath = base_path('image');
        $this->imageUrlBase = rtrim((string) env('APP_URL', 'http://localhost:8000'), '/') . '/image';

        if (!File::exists($this->imagePath)) {
            File::makeDirectory($this->imagePath, 0755, true);
        }
    }

    public function list(array $filters = [], bool $admin = false)
    {
        return $this->guideRepository->paginate($filters, $admin);
    }

    public function recent(int $limit = 4)
    {
        return $this->guideRepository->recent($limit);
    }

    public function popular(int $limit = 4)
    {
        return $this->guideRepository->popular($limit);
    }

    public function featured(int $limit = 6)
    {
        return $this->guideRepository->featured($limit);
    }

    public function byCategory(string $categorie, int $limit = 6)
    {
        return $this->guideRepository->byCategory($categorie, $limit);
    }

    public function categories(): array
    {
        return $this->guideRepository->categories();
    }

    public function findPublic(int $id): ?Guide
    {
        $guide = $this->guideRepository->findPublicById($id);

        if ($guide) {
            $guide->increment('vues');
        }

        return $guide;
    }

    public function findBySlug(string $slug): ?Guide
    {
        $guide = $this->guideRepository->findBySlug($slug);

        if ($guide) {
            $guide->increment('vues');
        }

        return $guide;
    }

    public function findAdmin(int $id): ?Guide
    {
        return $this->guideRepository->findById($id);
    }

    public function create(array $data, ?UploadedFile $image = null): Guide
    {
        $payload = $this->validate($data);
        $payload['popularite'] = (int) ($payload['popularite'] ?? 0);
        $payload['vues'] = (int) ($payload['vues'] ?? 0);
        $payload['ordre'] = (int) ($payload['ordre'] ?? 0);
        $payload['mis_en_avant'] = filter_var($payload['mis_en_avant'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Parse JSON fields that may come as strings from FormData
        foreach (['composants_recommandes', 'etapes', 'tags'] as $jsonField) {
            if (isset($payload[$jsonField]) && is_string($payload[$jsonField])) {
                $decoded = json_decode($payload[$jsonField], true);
                $payload[$jsonField] = is_array($decoded) ? $decoded : null;
            }
        }

        if (($payload['statut'] ?? null) === 'publie' && empty($payload['publie_le'])) {
            $payload['publie_le'] = now();
        }

        if ($image) {
            $payload['image_url'] = $this->storeImage($image);
        }

        return $this->guideRepository->create($payload);
    }

    public function update(int $id, array $data, ?UploadedFile $image = null): ?Guide
    {
        $guide = $this->guideRepository->findById($id);
        if (!$guide) {
            return null;
        }

        $payload = $this->validate($data, $id, true);

        // Parse JSON fields that may come as strings from FormData
        foreach (['composants_recommandes', 'etapes', 'tags'] as $jsonField) {
            if (isset($payload[$jsonField]) && is_string($payload[$jsonField])) {
                $decoded = json_decode($payload[$jsonField], true);
                $payload[$jsonField] = is_array($decoded) ? $decoded : null;
            }
        }

        if (isset($payload['mis_en_avant'])) {
            $payload['mis_en_avant'] = filter_var($payload['mis_en_avant'], FILTER_VALIDATE_BOOLEAN);
        }

        if (($payload['statut'] ?? null) === 'publie' && empty($payload['publie_le']) && !$guide->publie_le) {
            $payload['publie_le'] = now();
        }

        if ($image) {
            $this->deleteImage($guide->image_url);
            $payload['image_url'] = $this->storeImage($image);
        }

        return $this->guideRepository->update($id, $payload);
    }

    public function delete(int $id): bool
    {
        $guide = $this->guideRepository->findById($id);
        if (!$guide) {
            return false;
        }

        $this->deleteImage($guide->image_url);
        return $this->guideRepository->delete($id);
    }

    protected function validate(array $data, ?int $id = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $validator = Validator::make($data, [
            'titre' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('guides', 'slug')->ignore($id)],
            'resume' => [$required, 'string', 'max:1000'],
            'contenu' => [$required, 'string'],
            'categorie' => [$required, Rule::in(['guides-achat', 'actualites-tech', 'tutos-maintenance'])],
            'statut' => [$required, Rule::in(['publie', 'brouillon', 'archive'])],
            'badge' => ['nullable', 'string', 'max:60'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'composants_recommandes' => ['nullable'],
            'niveau' => ['nullable', 'string', 'max:40'],
            'difficulte' => ['nullable', 'string', Rule::in(['debutant', 'intermediaire', 'avance'])],
            'duree' => ['nullable', 'string', 'max:60'],
            'etapes' => ['nullable'],
            'video_url' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'mis_en_avant' => ['nullable'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'auteur' => ['nullable', 'string', 'max:120'],
            'temps_lecture' => ['nullable', 'string', 'max:40'],
            'popularite' => ['nullable', 'integer', 'min:0'],
            'vues' => ['nullable', 'integer', 'min:0'],
            'publie_le' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function storeImage(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new Exception('Format image non autorise. Utilisez jpg, jpeg, png ou webp.');
        }

        $filename = Str::uuid() . '.' . $extension;
        $file->move($this->imagePath, $filename);

        return $this->imageUrlBase . '/' . $filename;
    }

    protected function deleteImage(?string $url): void
    {
        if (!$url) {
            return;
        }

        $filename = basename((string) parse_url($url, PHP_URL_PATH));
        $path = $this->imagePath . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
