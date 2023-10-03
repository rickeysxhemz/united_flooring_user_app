<?php
namespace App\Services;

use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Libs\Response\GlobalApiResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Message;
use App\Helper\Helper;
use App\Models\User;
use Exception;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\CommonTrait;
use App\Models\Conversation;

class MessageService extends BaseService
{
    use CommonTrait;
    public function sendMessage($request)
    {
        try
        {
            DB::beginTransaction();
            $conversation_exists=Conversation::where('user_id',Auth::id())->where('admin_id',$request->receiver_id)->first();
            if($conversation_exists){
                $exists = Conversation::find($conversation_exists->id);
                
                $exists->message=$request->message;
                $exists->read=false;
                $exists->save();
                $message = new Message();
                $message->conversation_id=$conversation_exists->id;
                $message->sender_id = Auth::id();
                $message->receiver_id = $request->receiver_id;
                $message->message = $request->message;
                $message->save();
                $user = User::find(Auth::id());
                $title = 'new message';
                $body = $user->name.' send a message';
                $data = [
                    'status' => 'chat', 
                    'sender' =>  Auth::id(), 
                    'receiver' => $request->receiver_id, 
                    'message' => $request->message
                ];
                $this->pusher($request->receiver_id, $title, $body, $data);
            DB::commit();
            return $message;
            } else
            {
            $convesation=new Conversation();
            $convesation->user_id=Auth::id();
            $convesation->admin_id=$request->receiver_id;
            $convesation->message=$request->message;
            $convesation->save();
            $message = new Message();
            $message->conversation_id=$convesation->id;
            $message->sender_id = Auth::id();
            $message->receiver_id = $request->receiver_id;
            $message->message = $request->message;
            $message->save();
            $user = User::find(Auth::id());
                $title = 'new message';
                $body = $user->name.' send a message';

                $data = [
                        'status' => 'chat', 
                        'sender' =>  Auth::id(), 
                        'receiver' => $request->receiver_id, 
                        'message' => $request->message
                    ];

                $this->pusher($request->receiver_id, $title, $body, $data);
            DB::commit();
            return $message;
        }
    }
        catch(Exception $e){
            DB::rollback();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("MessageService: sendMessage", $error);
            return false;
            
        }
    }
    public function getChats()
    {
        try
        {
            $chats = Conversation::with('admin')
            ->where('user_id',Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            return Helper::returnRecord(GlobalApiResponseCodeBook::RECORDS_FOUND['outcomeCode'], $chats);
        }catch(Exception $e){
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("MessageService: getChats", $error);
            return false;
            
        }
    }
    public function getMessages()
    {
        try
        {
            $messages = Conversation::with([
                'messages' => function ($query) {
                    $query->with([
                        'sender:id,name,profile_image',
                        'receiver:id,name,profile_image'
                    ]);
                }
            ])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
            return Helper::returnRecord(GlobalApiResponseCodeBook::RECORDS_FOUND['outcomeCode'], $messages);
        }catch(Exception $e){
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("MessageService: getMessages", $error);
            return false;
            
        }
    }
    public function read()
    {
        try
        {
            $read = Conversation::where('user_id',Auth::id())->update(['read'=>true]);
            return Helper::returnRecord(GlobalApiResponseCodeBook::RECORDS_FOUND['outcomeCode'], $read);
        }catch(Exception $e){
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("MessageService: read", $error);
            return false;
            
        }
    }
}