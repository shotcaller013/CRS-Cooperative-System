<?php
// app/Http/Controllers/Api/SettingController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateCoopProfileRequest;
use App\Http\Resources\CoopProfileResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function getProfile(): CoopProfileResource
    {
        $this->authorize('viewAny', \App\Models\CoopProfile::class);
        return new CoopProfileResource($this->settings->getProfile());
    }

    public function updateProfile(UpdateCoopProfileRequest $request): CoopProfileResource
    {
        $profile = $this->settings->updateProfile($request->validated());
        return new CoopProfileResource($profile);
    }

    public function getPreferences(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\CoopProfile::class);
        return response()->json(['data' => $this->settings->getAllPreferences()]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $this->authorize('edit-setting');
        $this->settings->updatePreferences($request->all());
        return response()->json(['data' => $this->settings->getAllPreferences()]);
    }
}
