<?php

namespace App\Services;

use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Libs\Response\GlobalApiResponse;
use App\Helper\Helper;
use App\Models\Project;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Http\Traits\CommonTrait;
use App\Models\ProjectImage;
class ProjectService extends BaseService
{
    use CommonTrait;
        public function info($request)
        {
            try{
                $project=Project::with(['ProjectCategories','user','projectImages','comments'=>(function($query){
                    $query->with('sender')
                    ->orderBy('created_at','desc');
                })])
                ->where('id',$request->project_id)
                ->first();
                if(!$project)
                {
                    return $this->global_api_response->error(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS, "Project not found!", []);
                }
                return $project;
            }catch(Exception $e){
                DB::rollback();
                $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
                Helper::errorLogs("ProjectService: info", $error);
                return false;
            }
        }
        public function Comment($request)
        {
            try{
               
                        DB::beginTransaction();
                        $comment = new Comment();
                        $comment->sender_id = auth()->user()->id;
                        // $comment->receiver_id = $request->receiver_id;
                        $comment->project_id = $request->project_id;
                        $comment->comment = $request->comment;
                        $comment->save();
                        DB::commit();

                        $user_id=Project::where('id',$comment->project_id)->first()->admin_id;
                        $title='new comment';
                        $body=auth()->user()->name.' send a comment';
                        $user='user';
                        $data=[
                            'status'=>'comment',
                            'sender'=>auth()->user()->id,
                            'project_id'=>$request->project_id,
                            'comment'=>$request->comment
                        ];
                        $this->pusher(auth()->user()->id,$title,$body,$data);
                        $this->notifications($user_id, $user, $title, $body, $data);
                        return $comment;
            }catch(Exception $e){
                DB::rollback();
                $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
                Helper::errorLogs("ProjectService: Comment", $error);
                return false;
            }
        }
        public function getComments($request)
        {
            try{
                $comments=Comment::with('sender')
                ->where('project_id',$request->project_id)
                            ->orderBy('created_at','desc')
                            ->get();
                return $comments;
            }catch(Exception $e){
                DB::rollback();
                $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
                Helper::errorLogs("ProjectService: getComments", $error);
                return false;
            }
        }
       
}