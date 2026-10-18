<?php
/** Kernel smoke checks for milestone 2: routes still work without configured DB. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Core\{Application,Request,View};
$session = new Vereinssystem\Core\Session();$session->start();$_SESSION=[];
$app=Application::fromProjectRoot(dirname(__DIR__));$checks=0;
function check(bool $test,string $message): void { global $checks;++$checks;if(!$test){fwrite(STDERR,"FAIL {$message}\n");exit(1);}echo "OK {$message}\n"; }
$home=$app->handle(new Request('GET','/'));
check($home->status===303,'Home redirects');
$login=$app->handle(new Request('GET','/login'));
check($login->status===200,'Login page available without database');
check(str_contains($login->body,'database.local.php'),'Setup instructions visible');
check(str_contains($login->body,'vs-ui.css'),'UI CSS linked');
check(str_contains($login->body,'logo_head.png'),'Original logo used');
$json=$app->handle(new Request('GET','/status'));
check($json->status===200,'Status returns 200');
$data=json_decode($json->body,true,512,JSON_THROW_ON_ERROR);
check($data['version']==='3.0.0-alpha.2','Version reported');
check($app->handle(new Request('GET','/account'))->status===303,'Protected account requires login');
check($app->handle(new Request('GET','/rights'))->status===303,'Protected roles require login');
check($app->handle(new Request('POST','/status'))->status===405,'405 enforced');
check($app->handle(new Request('GET','/missing'))->status===404,'404 enforced');
check(View::escape('<script>')==='&lt;script&gt;','Output escape');
check(Request::normalizePath('/etc/..')==='/__invalid-route','Traversal path rejected');
check((new Request('GET','/login',[],[],'/verein/public/index.php'))->asset('css/vs-ui.css')==='/verein/public/assets/css/vs-ui.css','Subdirectory assets');
check((new Request('GET','/login',[],[],'/verein/public/index.php'))->url('/login')==='/verein/public/index.php?r=%2Flogin','Subdirectory query routes');
check(!is_file(dirname(__DIR__).'/login.php'),'No legacy login controller');
fwrite(STDOUT,"PASS: {$checks} kernel checks.\n");
