<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Image;
use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class PostController extends Controller
{
    public function index(Request $request)
    {

        $search = $request->query('search');
        
        $posts = Post::with(['image', 'user'])->when($search, function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10); // প্রতি পেজে 10টি পোস্ট

        return response()->json([
            'status' => 200,
            'data' => $posts
        ]);
    }


    public function createpost(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 401,
                "error" => $validator->errors()
            ]);
        }

        $post = new Post();
        $post->user_id = Auth::id();
        $post->title = $request->title;
        $post->body = $request->body;
        $post->save();

        if ($post) {

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('post', 'public');
                if ($path) {
                    $image = new Image();
                    $image->user_id = Auth::id();
                    $image->post_id = $post->id;
                    $image->image_url = $path;
                    $image->save();
                }
            }
        }

        if ($post) {
            return response()->json([
                "status" => 200,
                "message" => "post Created Succssefuly",
                "id" => $post->id
            ]);
        }
    }



    public function show(string $id)
    {

        $post = Post::with('image', 'user', 'comments')->find($id);

        if (!$post) {
            return response()->json([
                "status" => 404,
                "message" => "Post not found"
            ]);
        }

        // ভিউ কাউন্ট বাড়ানো
        $post->increment('view');


        return response()->json([
            "status" => 200,
            "data" => $post
        ]);
    }
    public function deletepost(string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                "status" => 404,
                "message" => "Post not found"
            ]);
        }

        if ($post->user_id !== Auth::id()) {
            return response()->json([
                "status" => 403,
                "error" => "You are not authorized to delete this post"
            ]);
        }

        // Fetch associated image
        $image = Image::where('post_id', $post->id)->first();

        if ($image) {

            if (Storage::disk('public')->exists($image->image_url)) {
                Storage::disk('public')->delete($image->image_url);
            }

            $image->delete();
        }

        $post->delete();

        return response()->json([
            "status" => 200,
            "message" => "post delete successfully"
        ]);
    }



    public function myposts()
    {

        $post = Post::with('image', 'user')->where('user_id', Auth::id())->orderBy('id', 'desc')->get();

        if (!$post) {
            return response()->json([
                "status" => 200,
                "data" => []
            ]);
        }


        return response()->json([
            "status" => 200,
            "data" => $post
        ]);
    }


    public function bookmark(Request $request)
    {


        $post = Post::find($request->post_id);
        if (!$post) {
            return response()->json([
                "status" => 404,
                "message" => "Post not found"
            ]);
        }

        // Check if the post is already bookmarked by the user
        $existingBookmark = Bookmark::where('user_id', Auth::id())
            ->where('post_id', $post->id)
            ->first();
        if ($existingBookmark) {
            return response()->json([
                "status" => 400,
                "message" => "Post already bookmarked"
            ]);
        }
        // Create a new bookmark


        $bookmark = new Bookmark();
        $bookmark->user_id = Auth::id();
        $bookmark->post_id = $post->id;
        $bookmark->save();

        return response()->json([
            "status" => 200,
            "message" => "Post bookmarked successfully"
        ]);
    }


    public function bookmarks()
    {
        $bookmarks = Bookmark::with('post')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            "status" => 200,
            "data" => $bookmarks
        ]);
    }


    public function unbookmark(string $id)
    {
        $bookmark = Bookmark::where('post_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$bookmark) {
            return response()->json([
                "status" => 404,
                "message" => "Bookmark not found"
            ]);
        }

        $bookmark->delete();

        return response()->json([
            "status" => 200,
            "message" => "Bookmark deleted successfully"
        ]);
    }



    public function like(Request $request)
    {
        $post = Post::find($request->post_id);
        if (!$post) {
            return response()->json([
                "status" => 404,
                "message" => "Post not found"
            ]);
        }

        // Check if the post is already liked by the user
        $existingLike = Like::where('user_id', Auth::id())
            ->where('post_id', $post->id)
            ->first();
        if ($existingLike) {
            return response()->json([
                "status" => 400,
                "message" => "Post already liked"
            ]);
        }

        // Create a new like
        $like = new Like();
        $like->user_id = Auth::id();
        $like->post_id = $post->id;
        $like->save();

        return response()->json([
            "status" => 200,
            "message" => "Post liked successfully"
        ]);
    }

    public function unlike(string $id)
    {
        $like = Like::where('post_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$like) {
            return response()->json([
                "status" => 404,
                "message" => "Like not found"
            ]);
        }

        $like->delete();

        return response()->json([
            "status" => 200,
            "message" => "Like removed successfully"
        ]);
    }


    public function comment (Request $request){
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'comment' => 'required|string|max:1000',
        ]);

        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->post_id = $request->post_id;
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json([
            "status" => 200,
            "message" => "Comment added successfully"
        ]);
    }


}
