<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\Response\GlobalApiResponse;
use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Services\SettingService;

class SettingController extends Controller
{
    public function __construct(SettingService $SettingService, GlobalApiResponse $GlobalApiResponse)
    {
        $this->setting_service = $SettingService;
        $this->global_api_response = $GlobalApiResponse;
    }
    public function editProfile(Request $request)
    {
        $edit_profile = $this->setting_service->editProfile($request);
        if (!$edit_profile)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Profile did not edited!", $edit_profile));
        return ($this->global_api_response->success(1, "Profile edited successfully!", $edit_profile));
    }
    public function profileImage(Request $request)
    {
        $profile_image = $this->setting_service->profileImage($request);
        if (!$profile_image)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Profile image did not uploaded!", $profile_image));
        return ($this->global_api_response->success(1, "Profile image uploaded successfully!", $profile_image));
    }
}
