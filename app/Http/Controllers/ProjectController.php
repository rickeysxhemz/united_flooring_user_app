<?php

namespace App\Http\Controllers;
use App\Libs\Response\GlobalApiResponse;
use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Http\Requests\ProjectRequest\InfoRequest;
use Illuminate\Http\Request;
use App\Services\ProjectService;
use App\Http\Requests\ProjectRequest\CommentRequest;
use App\Http\Requests\ProjectRequest\GetCommentRequest;

class ProjectController extends Controller
{
    public function __construct(ProjectService $ProjectService, GlobalApiResponse $GlobalApiResponse)
    {
        $this->project_service = $ProjectService;
        $this->global_api_response = $GlobalApiResponse;
    }
    public function info(InfoRequest $request)
    {
        $info = $this->project_service->info($request);
        if (!$info)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Project Update Info did not fetched!", $info));
        return ($this->global_api_response->success(1, "Product Update Info fetched successfully!", $info));
    }
    public function comment(CommentRequest $request)
    {
        $comment = $this->project_service->comment($request);
        if (!$comment)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Comment did not added!", $comment));
        return ($this->global_api_response->success(1, "Comment added successfully!", $comment));
    }
    public function getComments(GetCommentRequest $request)
    {
        $get_comments = $this->project_service->getComments($request);
        if (!$get_comments)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Comments did not fetched!", $get_comments));
        return ($this->global_api_response->success(1, "Comments fetched successfully!", $get_comments));
    }
}
