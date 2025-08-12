<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function signin(Request $request)

    {
        $validated = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 401,
                'error' => $validated->errors()

            ]);
        }


        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = User::find(Auth::id());

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'token' => $token
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'error' => 'incaret passwor or email'
            ]);
        }
    }


    public function signup(Request $request)

    {
        $validated = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'required|string|max:255',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 401,
                'error' => $validated->errors()

            ]);
        }


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if ($user) {

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'token' => $token
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'error' => 'incaret passwor or email'
            ]);
        }
    }


    public function updateprofile(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bio' => 'nullable|max:500',
            'profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // optional but recommended
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 401,
                'error' => $validated->errors()

            ]);
        }


        $user = User::find(Auth::id());

        if (!$user) {
            return response()->json([
                'status' => 404,
                'error' => 'User not found'
            ]);
        }


        $user->name = $request->name;
        $user->bio = $request->bio;


        if ($request->hasFile('profile')) {

            if ($user->profile && Storage::disk('public')->exists($user->profile)) {
                Storage::disk('public')->delete($user->profile);
            }

            //unlink 

            $file = $request->file('profile');
            $path = $file->store('profile', 'public');
            $user->profile = $path;
        }

        $user->save();

        return response()->json([
            'status' => 200,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }


    public function signout(Request $request)
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully signed out'
        ]);
    }
}
