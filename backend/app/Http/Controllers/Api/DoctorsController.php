<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

class DoctorsController extends Controller
{
    public function index(): JsonResponse
    {
        $doctors = Doctor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'position' => $doctor->position,
                'regalia' => $doctor->regalia,
                'description' => $doctor->description,
                'photo_url' => MediaUrl::toUrl($doctor->photo),
                'sort_order' => $doctor->sort_order,
            ])
            ->values();

        return response()->json($doctors);
    }
}
