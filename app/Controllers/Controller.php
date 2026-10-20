<?php
/** Shared HTML composition and access-denied pages for server-rendered MVC controllers. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\User;
use Vereinssystem\Core\Csrf;
use Vereinssystem\Core\Request;
use Vereinssystem\Core\Response;
use Vereinssystem\Core\View;

abstract class Controller
{
    public function __construct(protected readonly View $views,protected readonly Csrf $csrf) {}

    /** @param array<string,mixed> $data */
    protected function page(Request $request,string $title,string $template,array $data=[],?User $user=null,int $status=200): Response
    {
        $content=$this->views->render($template,$data);
        $html=$this->views->render('layouts/app',[
            'title'=>$title,'content'=>$content,'homeUrl'=>$request->url('/'),
            'cssUrl'=>$request->asset('css/vs-ui.css'),
            'jsUrl'=>$request->asset('js/vs-ui.js'),
            'logoUrl'=>$request->asset('images/logo_head.png'),
            'user'=>$user,'csrfToken'=>$user!==null?$this->csrf->token():null,
            'accountUrl'=>$request->url('/account'),'dashboardUrl'=>$request->url('/dashboard'),
            'membersUrl'=>$request->url('/members'),'rightsUrl'=>$request->url('/rights'),
            'adminUrl'=>$request->url('/admin/roles'),
            'placesUrl'=>$request->url('/places'),'departmentsUrl'=>$request->url('/departments'),
            'eventsUrl'=>$request->url('/events'),'newsUrl'=>$request->url('/news'),'logoutUrl'=>$request->url('/logout'),
        ]);
        return new Response($html,$status,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
    }

    protected function forbidden(Request $request): Response
    {
        return new Response($this->views->render('errors/status',[
            'status'=>403,'message'=>'Kein Zugriff auf diesen Bereich',
            'url'=>$request->url('/account'),'asset'=>$request->asset('css/vs-ui.css'),
        ]),403,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
    }

    protected function badToken(Request $request): Response
    {
        return new Response($this->views->render('errors/status',[
            'status'=>419,'message'=>'Formular abgelaufen. Bitte Seite neu laden.',
            'url'=>$request->url('/login'),'asset'=>$request->asset('css/vs-ui.css'),
        ]),419,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
    }
}
