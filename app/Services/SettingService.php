<?php
namespace App\Services;

use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Libs\Response\GlobalApiResponse;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Helper\Helper;
use Illuminate\Support\Facades\DB;

class SettingService extends BaseService{
    public function editProfile($request){
        try{
            $user=auth()->user();
            if(isset($request->name))
            {
            $user->name=$request->name;
            }
            if(isset($request->email))
            {
            $user->email=$request->email;
            }
            if(isset($request->phone))
            {
                $user->phone=$request->phone;
            }
            $user->save();
            return $user;
        }catch(Exception $e){
            DB::rollback();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("SettingService: editProfile", $error);
            return false;
        }
    }
    public function profileImage($request){
        try{
            $user=auth()->user();
            if(isset($request->image_url))
            {
            $user->profile_image=Helper::storeImageUrl($request,null,'storage/userImages');
            }
            $user->save();
            return $user;
        }catch(Exception $e){
            DB::rollback();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("SettingService: profileImage", $error);
            return false;
        }
    }

}