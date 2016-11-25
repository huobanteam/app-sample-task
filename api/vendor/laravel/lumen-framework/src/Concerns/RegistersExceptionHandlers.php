<?php

namespace Laravel\Lumen\Concerns;

use Error;
use Exception;
use ErrorException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

trait RegistersExceptionHandlers
{
    /**
     * Throw an HttpException with the given data.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function abort($code, $message = '', array $headers = [])
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        }

        throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Set the error handling for the application.
     *
     * @return void
     */
    protected function registerErrorHandling()
    {
        error_reporting(E_ALL ^ E_NOTICE);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                $warning_handler = new StreamHandler(storage_path('logs/error.log'), Logger::WARNING);
                $logger = new \Monolog\Logger('warning', array($warning_handler));
                $error = $level . ' ' . $message . ' on line ' . $line . ' in ' . $file;
                $logger->error($error);

                // throw new ErrorException($message, 0, $level, $file, $line);
                $result = array(
                    'code' => 500,
                    'message' => '伙伴任务应用暂时无法响应您的请求',
                );

                $response = response()->json($result, 500);
                $response->send();
                return;
            }
        });

        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });

        register_shutdown_function(function () {
            $this->handleShutdown();
        });
    }

    /**
     * Handle the application shutdown routine.
     *
     * @return void
     */
    protected function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {

            $warning_handler = new StreamHandler(storage_path('logs/error.log'), Logger::ERROR);
            $logger = new \Monolog\Logger('error', array($warning_handler));
            $error = $error['type'] . ' ' . $error['message'] . ' on line ' . $error['line'] . ' in ' . $error['file'];
            $logger->error($error);

            $result = array(
                'code' => 500,
                'message' => '伙伴任务应用暂时无法响应您的请求',
            );

            $response = response()->json($result, 500);
            $response->send();
            return;

            // $this->handleUncaughtException(new FatalErrorException(
            //     $error['message'], $error['type'], 0, $error['file'], $error['line']
            // ));
        }
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatalError($type)
    {
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }

        return in_array($type, $errorCodes);
    }

    /**
     * Send the exception to the handler and return the response.
     *
     * @param  \Throwable  $e
     * @return Response
     */
    protected function sendExceptionToHandler($e)
    {
        $handler = $this->resolveExceptionHandler();

        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        $handler->report($e);

        return $handler->render($this->make('request'), $e);
    }

    /**
     * Handle an uncaught exception instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function handleUncaughtException($e)
    {
        $handler = $this->resolveExceptionHandler();

        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        $handler->report($e);

        if ($this->runningInConsole()) {
            $handler->renderForConsole(new ConsoleOutput, $e);
        } else {
            $handler->render($this->make('request'), $e)->send();
        }
    }

    /**
     * Get the exception handler from the container.
     *
     * @return mixed
     */
    protected function resolveExceptionHandler()
    {
        if ($this->bound('Illuminate\Contracts\Debug\ExceptionHandler')) {
            return $this->make('Illuminate\Contracts\Debug\ExceptionHandler');
        } else {
            return $this->make('Laravel\Lumen\Exceptions\Handler');
        }
    }
}
