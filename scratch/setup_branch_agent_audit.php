<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.default') !== 'sqlite' || realpath(config('database.connections.sqlite.database')) !== realpath(base_path('scratch/filament-native-ui.sqlite'))) throw new RuntimeException('Isolated testing database required.');
$root=base_path('scratch/branch-agent-audit/private');
if(!is_dir($root)) mkdir($root,0700,true);
$fixtures=[];
foreach(['bogor','depok'] as $slug){
 $branch=App\Models\Branch::where('slug',$slug)->firstOrFail();
 $token=bin2hex(random_bytes(32)); $device=(string)Illuminate\Support\Str::uuid();
 $agent=App\Models\PrintAgent::firstOrNew(['branch_id'=>$branch->id]);
 $agent->forceFill(['id'=>$agent->id??(string)Illuminate\Support\Str::uuid(),'name'=>'Audit '.$slug,'printer_name'=>'Audit Printer '.$slug,'enabled'=>true,'revoked'=>false,'token_hash'=>hash('sha256',$token),'device_id'=>$device,'heartbeat_at'=>now(),'printer_ready'=>true])->save();
 App\Models\PrintJob::where('print_agent_id',$agent->id)->whereIn('status',['queued','processing'])->update(['status'=>'unknown','finished_at'=>now(),'failure_code'=>'audit_reset']);
 $fixtures[$slug]=['server_url'=>'http://127.0.0.1:7102','agent_id'=>$agent->id,'device_id'=>$device,'branch_code'=>$branch->code,'branch_id'=>$branch->id,'printer_name'=>$agent->printer_name,'allow_http'=>true,'token'=>$token];
}
file_put_contents($root.'/fixtures.json',json_encode($fixtures,JSON_PRETTY_PRINT));
$admin=App\Models\User::where('email','admin@example.com')->firstOrFail();
$zip=app(App\Services\Printing\AgentPackageBuilder::class)->build($agent,'http://127.0.0.1:7102',$admin);
copy($zip,$root.'/installer.zip'); unlink($zip);
echo 'Two isolated branch agents and one installer fixture prepared. No physical printing.'.PHP_EOL;
