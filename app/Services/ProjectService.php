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
                $project=Project::with('ProjectCategories','user','projectImages','comments')
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
                        $comment->receiver_id = $request->receiver_id;
                        $comment->project_id = $request->project_id;
                        $comment->comment = $request->comment;
                        $comment->save();
                        DB::commit();
        
                        $title='new comment';
                        $body=auth()->user()->name.' send a comment';
                        $data=[
                            'status'=>'comment',
                            'sender'=>auth()->user()->id,
                            'receiver'=>$request->receiver_id,
                            'project_id'=>$request->project_id,
                            'comment'=>$request->comment
                        ];
                        $this->pusher($request->receiver_id,$title,$body,$data);
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
                $comments=Comment::with('sender','receiver')
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
}