<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosBlogPost;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DateTime;

class AdminBlogController extends Controller
{
    use Functions;

    /**
     * API for list of Blog Post
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $PocomosBlogPost = PocomosBlogPost::orderBy('id', 'desc')->where('active', 1);

        if ($request->search) {
            $PocomosBlogPost->where(function ($PocomosBlogPost) use ($request) {
                $PocomosBlogPost->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('date_posted', 'like', '%' . $request->search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosBlogPost->count();
        $PocomosBlogPost->skip($perPage * ($page - 1))->take($perPage);

        $PocomosBlogPost = $PocomosBlogPost->get();

        return $this->sendResponse(true, 'List of Blog Post.', [
            'blog_posts' => $PocomosBlogPost,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Blog Post
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosBlogPost = PocomosBlogPost::find($id);
        if (!$PocomosBlogPost) {
            return $this->sendResponse(false, 'Blog Post Not Found');
        }
        return $this->sendResponse(true, 'Blog Post details.', $PocomosBlogPost);
    }

    /**
     * API for create of Blog Post
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'title' => 'required',
            'body' => 'required',
            'date_posted' => 'required|date',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('title', 'body', 'date_posted');
        $input_details['active'] = 1;

        $PocomosBlogPost =  PocomosBlogPost::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Blog Post created successfully.', $PocomosBlogPost);
    }

    /**
     * API for update of Blog Post
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'blog_post_id' => 'required|exists:pocomos_blog_posts,id',
            'title' => 'required',
            'body' => 'nullable',
            'date_posted' => 'required|date',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosBlogPost = PocomosBlogPost::find($request->blog_post_id);

        if (!$PocomosBlogPost) {
            return $this->sendResponse(false, 'Blog Post not found.');
        }

        $PocomosBlogPost->update(
            $request->only('title', 'body', 'date_posted')
        );

        return $this->sendResponse(true, 'Blog Post updated successfully.', $PocomosBlogPost);
    }

    /**
     * API for Blog Post.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosBlogPost = PocomosBlogPost::find($id);
        if (!$PocomosBlogPost) {
            return $this->sendResponse(false, 'Blog Post not found.');
        }

        $PocomosBlogPost->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Blog Post deleted successfully.');
    }

    public function messageboardpost(Request $request)
    {
        $datetime = new DateTime('tomorrow');
        $tomorrow = $datetime->format('Y-m-d');

        $PocomosBlogPost = PocomosBlogPost::where('date_posted', '<', $tomorrow)->where('active', 1)->orderBy('date_posted', 'desc')->first();

        return $this->sendResponse(true, 'Blog Post.', [
            'blog_posts' => $PocomosBlogPost,
        ]);
    }
}
