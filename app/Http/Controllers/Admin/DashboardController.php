<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\YouTubeChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class DashboardController extends Controller {
 public function __invoke(): View {
  $users=User::count(); $channels=YouTubeChannel::count();
  $supporters=DB::table('supporter_statuses')->where('status','active')->where('ends_at','>',now())->count();
  $metrics=DB::table('youtube_metrics_monthly')->orderByDesc('metric_month')->limit(24)->get();
  return view('admin.dashboard',compact('users','channels','supporters','metrics'));
 }
}