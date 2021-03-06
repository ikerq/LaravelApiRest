<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        //Error controlado para las validaciones
        if($exception instanceof ValidationException){
            return $this->convertValidationExceptionToResponse($exception, $request);
        }
        //Error controlado para los errores de modelo (eje no se consigue un usuario)
        if($exception instanceof ModelNotFoundException){
            $modelo = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("No existe ninguna instancia de {$modelo} con el id especificado", 404);
        }
        //Validacion de Autenticación
        if($exception instanceof AuthenticationException){
            return $this->unauthenticated($request, $exception);
        }
        //Validacion de autorización
        if($exception instanceof AuthorizationException){
            return $this->errorResponse("No posee permisos para ejecutar esta acción", 403);
        }
        //Error de pagina no encontrada
        if($exception instanceof NotFoundHttpException) {
            return $this->errorResponse('NO se encontró la URL especificada', 404);
        }
        //Error metodo no permitido
        if($exception instanceof MethodNotAllowedHttpException){
            return $this->errorResponse('El método especificado en la petición no es válido',405);
        }
        //Se coloca un condicional para cualquier otra exception de tipo HttpException (recordar que esta referencia debe ser de symfony {según tutorial de udemy})
        if($exception instanceof HttpException){
            return $this->errorResponse($exception->getMessage(),$exception->getStatusCode());
        }
        if($exception instanceof QueryException){
            //dd($exception);
            $codigo = $exception->errorInfo[1];
            if($codigo == 1451){//Delete de un registro con constraint con otro tabla
                return $this->errorResponse('No se puede eliminar de forma permanente el recurso porque está relacionado con algún otro', 409);
            }
        }
        //Si estamos en modo debug se envia el detalle de la excepcion
        if(config('app.debug')) {
            return parent::render($request, $exception);
        }
        //Si estamos en modo produccion solo se envia un msj genérico
        return $this->errorResponse('Falla inesperada. Intente luego', 500);
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
        return $this->errorResponse('No autenticado', 401);

        return redirect()->guest(route('login'));
    }
    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors = $e->validator->errors()->getMessages();
        return $this->errorResponse($errors, 422);
    }
}
