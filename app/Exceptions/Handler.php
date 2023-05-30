<?php

namespace App\Exceptions;

use Throwable;
use App\Http\Controllers\Functions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use Functions;

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
        'current_password',
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
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        //dd($exception->getFile(),  $exception->getMessage(), $exception->getLine());
        /**Model not found exception for in case not find any id base model then this exception is accured */
        if ($exception instanceof ModelNotFoundException) {
            $model = (explode("\\", $exception->getModel()));
            $model = end($model);
            return $this->sendResponse(false, __('strings.model_not_found', ['model' => $model]));
        }

        /**Call any not exist API then fire this handler */
        if ($exception instanceof NotFoundHttpException) {
            return $this->sendResponse(false, __('strings.end_point_not_found'));
        }

        /**Exception */
        if ($exception instanceof \Exception) {
            return $this->sendResponse(false, $exception->getMessage());
        }

        return parent::render($request, $exception);
    }
}
