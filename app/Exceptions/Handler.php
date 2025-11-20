<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

     /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api*')) {
            return response()->json(['error' => 'unauthenticated', 'message' => __('auth.unauthenticated')], 401);
        }

        return redirect()->guest(route('login'));
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->view('errors.route404', [], 404);
        }
    if ($request->expectsJson() || $request->ajax()) {
        return response()->json([
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            // خلي التريس يظهر بس لو APP_DEBUG=true
            'trace'   => config('app.debug') ? collect($e->getTrace())->take(5) : [],
        ], 500);
    }

    return parent::render($request, $e);

    }
}
