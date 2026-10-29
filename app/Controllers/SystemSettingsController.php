<?php
/** Administrator-only settings pages; rights and CSRF enforced by route middleware. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use DomainException;
use Vereinssystem\Auth\{AuthService,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\Settings\SettingsService;

final class SystemSettingsController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?SettingsService $settings,private readonly Session $session)
    {parent::__construct($views,$csrf);}

    private function user():User
    {return $this->auth?->currentUser()??throw new \LogicException('Auth-Middleware fehlt.');}
    private function service():SettingsService
    {return $this->settings??throw new DatabaseUnavailable('Systemeinstellungen noch nicht eingerichtet. Bitte Migration 008 ausführen.');}

    public function index(Request $request):Response
    {
        $flash=$this->session->get('system_settings_notice',null);
        $this->session->forget('system_settings_notice');
        return $this->page($request,'Systemeinstellungen','settings/index',[
            'snapshot'=>$this->service()->snapshot(),
            'notice'=>is_array($flash)?$flash:null,
            'saveUrl'=>$request->url('/admin/settings/save'),
            'logsUrl'=>$request->url('/admin/settings/logs'),
            'csrfToken'=>$this->csrf->token(),
        ],$this->user());
    }

    public function save(Request $request):Response
    {
        $input=[];
        foreach(SettingsService::DEFAULTS as $key=>$_){
            // Checkbox fields are explicitly set to 0 when absent; other fields require values.
            if(str_starts_with($key,'notify_'))$input[$key]=$request->inputString($key,'0');
            else $input[$key]=$request->inputString($key);
        }
        $rev=filter_var($request->inputString('revision'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        try{
            if(!is_int($rev))throw new DomainException('Ungültige Versionsnummer. Bitte Seite neu laden.');
             $newRevision=$this->service()->save($input,$rev,$this->user()->id);
            $notice=['type'=>'success','message'=>$newRevision===$rev
                ? 'Keine Änderung notwendig: Einstellungen sind bereits aktuell.'
                : 'Systemeinstellungen gespeichert. Änderungen wurden protokolliert.'];
        }catch(DomainException $e){$notice=['type'=>'error','message'=>$e->getMessage()];}
        $this->session->set('system_settings_notice',$notice);
        return Response::redirect($request->url('/admin/settings'));
    }

    public function logs(Request $request):Response
    {
        $page=filter_var($request->queryString('page','1'),FILTER_VALIDATE_INT,
            ['options'=>['min_range'=>1,'max_range'=>1000]]);
        $page=is_int($page)?$page:1;
        return $this->page($request,'Änderungsprotokoll','settings/logs',[
            'history'=>$this->service()->history($page),
            'pageNumber'=>$page,'backUrl'=>$request->url('/admin/settings'),
            'nextUrl'=>$request->url('/admin/settings/logs').'&page='.($page+1),
            'prevUrl'=>$request->url('/admin/settings/logs').'&page='.max(1,$page-1),
        ],$this->user());
    }
}
