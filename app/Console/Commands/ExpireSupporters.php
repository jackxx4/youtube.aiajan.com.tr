<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ExpireSupporters extends Command {
 protected $signature='supporters:expire';
 protected $description='Süresi dolan destekçi statülerini pasife alır.';
 public function handle(): int {
  $count=DB::table('supporter_statuses')->where('status','active')->where('ends_at','<=',now())->update(['status'=>'expired','updated_at'=>now()]);
  $this->info($count.' destekçi kaydı kapatıldı.');
  return self::SUCCESS;
 }
}