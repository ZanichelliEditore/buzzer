<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Http\Repositories\PublisherRepository;

class BasicAuth
{
    protected $publisherRepository;

    public function __construct(PublisherRepository $publisher){

        $this->publisherRepository = $publisher;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        return Auth::guard('api')->onceBasic('username') ?: $next($request);
    }
}
