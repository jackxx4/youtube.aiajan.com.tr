<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
class ProfileController extends Controller {
    public function show(Request $request, ?string $username=null): View {
        $profile=$username ? User::where('username',$username)->where('status','active')->firstOrFail() : $request->user();
        $channel=$profile->activeChannelLink()->with('channel')->first()?->channel;
        return view('profile.show',compact('profile','channel'));
    }
}