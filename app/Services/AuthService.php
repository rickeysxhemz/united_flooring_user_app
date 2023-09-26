<?php

namespace App\Services;

use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Models\SocialIdentity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PasswordReset;
use App\Helper\Helper;
use App\Models\User;
use App\Models\OTP;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Exception;
use Twilio\Rest\Client;
use App\Models\Setting;
use App\Models\PhoneVerify;
class AuthService extends BaseService
{
 
    public function login($request)
    {
        try {
         
            $credentials = $request->only('token');
            $user = User::whereHas('roles', function ($q) {
                $q->where('name', 'user');
            })
                ->where('remember_token', '=', $credentials['token'])
                ->first();

            $login=[];
            $login['email']=$user->email;
            $login['password']=$user->remember_token;
            // if(isset($user->phone_verified_at) && $user->phone_verified_at !== null){
                if (
                    Hash::check($credentials['token'], isset($user->password) ? $user->password : null)
                    &&
                    $token = $this->guard()->attempt($login)
                ) {
    
                    $roles = Auth::user()->roles->pluck('name');
                    $data = Auth::user()->toArray();
                    unset($data['roles']);
    
                    $data = [
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'expires_in' => $this->guard()->factory()->getTTL() * 60,
                        'user' => Auth::user()->only('id', 'name', 'email', 'phone_no', 'profile_image'),
                        'roles' => $roles,
                        'settings' => Auth::user()->setting->only('user_id', 'private_account', 'secure_payment', 'sync_contact_no', 'app_notification', 'language')
                    ];
                    return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $data);
                }
                return Helper::returnRecord(GlobalApiResponseCodeBook::INVALID_CREDENTIALS['outcomeCode'], []);
            // }
            return Helper::returnRecord(GlobalApiResponseCodeBook::INVALID_CREDENTIALS['outcomeCode'], []);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: login", $error);
            return false;
        }
    }
    
  

    public function logout()
    {
        try {
            Auth::logout();
            return true;
        } catch (Exception $e) {

            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: logout", $error);
            return false;
        }
    }
    
    public function refresh()
    {
        try {
            $token = $this->guard()->refresh();
            $roles = Auth::user()->roles->pluck('name');
            $data = Auth::user()->toArray();
            unset($data['roles']);

            $data = [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
                'user' => Auth::user()->only('id', 'name', 'email', 'phone_no', 'profile_image'),
                'roles' => $roles,
                'settings' => Auth::user()->setting->only('user_id', 'private_account', 'secure_payment', 'sync_contact_no', 'app_notification', 'language')
            ];

            return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $data);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: refresh", $error);
            return false;
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function handleProviderCallback($provider)
    {
        try {
            $providerUser = Socialite::driver($provider)->user();
            return $user = $this->findOrCreateUser($providerUser, $provider);

//            $data = Auth::user()->toArray();
//            unset($data['roles']);
//
//            $data = [
//                'access_token' => $token,
//                'token_type' => 'bearer',
//                'expires_in' => $this->guard()->factory()->getTTL() * 60,
//                'user' => Auth::user()->only('id', 'username', 'email', 'phone_no', 'address', 'experience', 'cv_url', 'image_url', 'total_balance', 'absolute_cv_url', 'absolute_image_url'),
//                'roles' => $roles,
//                'settings' => Auth::user()->setting->only('user_id', 'private_account', 'secure_payment', 'sync_contact_no', 'app_notification', 'language')
//            ];
//
//            return $data;

            //return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $data);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: handleProviderCallback", $error);
            return false;
        }
    }

    public function findOrCreateUser($providerUser, $provider)
    {
        $account = SocialIdentity::whereProviderName($provider)
            ->whereProviderId($providerUser->getId())
            ->first();

        if ($account) {
            return $account->user;
        } else {
            $user = User::whereEmail($providerUser->getEmail())->first();

            if (!$user) {

                $user = User::create([
                    'email' => $providerUser->getEmail(),
                    'name' => $providerUser->getName(),
                    'password' => '$2y$10$zzp91bknlK3h3PPh3/xanuZFoE81aIsbn0THkGqZRm2RzCV8f082C',
                    'image_url' => $providerUser->avatar,
                    'user_verified_at' => Carbon::now(),
                ]);

                $user_role = Role::findByName('user');
                $user_role->users()->attach($user->id);

                $setting = new Setting();
                $setting->user_id = $user->id;
                $setting->private_account = 0;
                $setting->secure_payment = 1;
                $setting->sync_contact_no = 0;
                $setting->app_notification = 1;
                $setting->save();
            }

            $user->identities()->create([
                'provider_id' => $providerUser->getId(),
                'provider_name' => $provider,
            ]);

            return $user;
        }
    }


     /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}