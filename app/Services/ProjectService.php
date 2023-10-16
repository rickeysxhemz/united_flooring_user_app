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
use App\Models\ProjectComment;
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
                        $comment_exist=ProjectComment::where('project_id',$request->project_id)->first();
                        if($comment_exist)
                        {
                            $exists=ProjectComment::find($comment_exist->id);
                            $exists->unread_comments_count=$exists->unread_comments_count+1;
                            $exists->read=false;
                            $exists->save();
                        }else{
                        $project_comment = new ProjectComment();
                        $project_comment->project_id = $request->project_id;
                        $project_comment->user_id = auth()->user()->id;
                        $project_comment->read = false;
                        $project_comment->unread_comments_count = 1;
                        $project_comment->save();
                        }
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
    public function uploadImages($request)
    {
        try{
            DB::beginTransaction();
            $project_images=new ProjectImage;
            $project_images->project_id=$request->project_id;
            $project_images->image=Helper::storeImageUrl($request,null,'storage/projectImages');
            $project_images->save();
            DB::commit();
            return $project_images;
        }catch(Exception $e){
            DB::rollback();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("ProjectService: uploadImages", $error);
            return false;
        }
    }
    public function readComment($request)
    {
        try{
            DB::beginTransaction();
            $project_comment=ProjectComment::where('project_id',$request->project_id)->first();
            $project_comment->unread_comments_count=0;
            $project_comment->read=true;
            $project_comment->save();
            DB::commit();
            return $project_comment;
        }catch(Exception $e){
            DB::rollback();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("ProjectService: readComment", $error);
            return false;
        }
    }
}