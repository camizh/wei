<?php
/**
 * Wei Framework
 *
 * @copyright   Copyright (c) 2008-2015 Twin Huang
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 */

namespace Wei;

/**
 * A service that handles exception and display pretty exception message
 *
 * @property    Logger $logger The logger wei
 * @property    Response $response The HTTP response wei
 */
class Error extends Base
{
    /**
     * The default error message display when debug is not enable
     *
     * @var string
     */
    protected $message = 'Error';

    /**
     * The detail error message display when debug is not enable
     *
     * @var string
     */
    protected $detail = 'Unfortunately, an error occurred. Please try again later.';

    /**
     * The detail error message display when thrown 404 exception
     *
     * @var string
     */
    protected $notFoundDetail = 'Sorry, the page you requested was not found. Please check the URL and try again.';

    /**
     * Whether ignore the previous exception handler or attach it again to the
     * exception event
     *
     * @var bool
     */
    protected $ignorePrevHandler = false;

    /**
     * The previous exception handler
     *
     * @var null|callback
     */
    protected $prevExceptionHandler;

    /**
     * The custom error handlers
     *
     * @var array
     */
    protected $handlers = array(
        'error'     => array(),
        'fatal'     => array(),
        'notFound'  => array()
    );

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->registerErrorHandler();
        $this->registerExceptionHandler();
        $this->registerFatalHandler();
    }

    /**
     * Attach a handler to exception error
     *
     * @param callback $fn The error handler
     * @return $this
     */
    public function __invoke($fn)
    {
        $this->handlers['error'][] = $fn;
        return $this;
    }

    /**
     * Attach a handler to not found error
     *
     * @param callable $fn The error handler
     * @return $this
     */
    public function notFound($fn)
    {
        $this->handlers['notFound'][] = $fn;
        return $this;
    }

    /**
     * Attach a handler to fatal error
     *
     * @param callable $fn The error handler
     * @return $this
     */
    public function fatal($fn)
    {
        $this->handlers['fatal'][] = $fn;
        return $this;
    }

    /**
     * Register exception Handler
     */
    protected function registerExceptionHandler()
    {
        $this->prevExceptionHandler = set_exception_handler(array($this, 'handleException'));
    }

    /**
     * Register error Handler
     */
    protected function registerErrorHandler()
    {
        set_error_handler(array($this, 'handleError'));
    }

    /**
     * Detect fatal error and register fatal handler
     */
    protected function registerFatalHandler()
    {
        $error = $this;

        // When shutdown, the current working directory will be set to the web
        // server directory, store it for later use
        $cwd = getcwd();

        register_shutdown_function(function() use($error, $cwd) {
            $e = error_get_last();
            if (!$e || !in_array($e['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
                // No error or not fatal error
                return;
            }

            ob_get_length() && ob_end_clean();

            // Reset the current working directory to make sure everything work as usual
            chdir($cwd);

            $exception = new \ErrorException($e['message'], $e['type'], 0, $e['file'], $e['line']);

            if ($error->triggerHandler('fatal', $exception)) {
                // Handled!
                return;
            }

            // Fallback to error handlers
            if ($error->triggerHandler('error', $exception)) {
                // Handled!
                return;
            }

            // Fallback to internal error Handlers
            $error->internalHandleException($exception);
        });
    }

    /**
     * Trigger a error handler
     *
     * @param string $type The type of error handlers
     * @param \Exception $exception
     * @return bool
     */
    public function triggerHandler($type, \Exception $exception)
    {
        foreach ($this->handlers[$type] as $handler) {
            $result = call_user_func_array($handler, array($exception, $this->wei));
            if (true === $result) {
                return true;
            }
        }
        return false;
    }

    /**
     * The exception handler to render pretty message
     *
     * @param \Exception $exception
     */
    public function handleException(\Exception $exception)
    {
        if (!$this->ignorePrevHandler && $this->prevExceptionHandler) {
            call_user_func($this->prevExceptionHandler, $exception);
        }

        if (404 == $exception->getCode()) {
            if ($this->triggerHandler('notFound', $exception)) {
                return;
            }
        }

        if (!$this->triggerHandler('error', $exception)) {
            $this->internalHandleException($exception);
        }

        restore_exception_handler();
    }

    public function internalHandleException(\Exception $exception)
    {
        $debug = $this->wei->isDebug();
        $code = $exception->getCode();

        // HTTP status code
        if ($code < 100 || $code > 600) {
            $code = 500;
        }

        // Logger level
        if ($code >= 500) {
            $level = 'critical';
        } else {
            $level = 'info';
        }

        try {
            // The flowing services may throw exception too
            $this->response->setStatusCode($code)->send();
            $this->logger->log($level, $exception);

            $this->renderException($exception, $debug);
        } catch (\Exception $e) {
            $this->renderException($e, $debug);
        }
    }

    /**
     * Render exception message
     *
     * @param \Exception $e
     * @param bool $debug Whether show debug trace
     */
    public function renderException(\Exception $e, $debug)
    {
        $code = $e->getCode();
        $file = $e->getFile();
        $line = $e->getLine();

        // Prepare message
        if ($debug) {
            $message = $e->getMessage();
            $detail = sprintf('Threw by %s in %s on line %s', get_class($e), $file, $line);
        } else {
            $message = $this->message;
            $detail = $this->detail;
        }

        if ($code == 404) {
            $message = $e->getMessage();
            if (!$debug) {
                $detail = $this->notFoundDetail;
            }
        }
        $message = htmlspecialchars($message, ENT_QUOTES);

        // Render error views
        if ($debug) {
            $mtime      = date('Y-m-d H:i:s', filemtime($file));
            $fileInfo   = $this->getFileCode($file, $line);
            $trace      = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES);

            $detail = "<h2>File</h2>"
                . "<p class=\"error-text\">$file modified at $mtime</p>"
                . "<p><pre>$fileInfo</pre></p>"
                . "<h2>Trace</h2>"
                . "<p class=\"error-text\">$detail</p>"
                . "<p><pre>$trace</pre></p>";
        } else {
            $detail = "<p>$detail</p>";
        }

        $html = '<!DOCTYPE html>'
            . '<html>'
            . '<head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'
            . "<title>$message</title>"
            . '<style type="text/css">'
            . 'body { font-size: 12px; color: #333; padding: 15px 20px 20px 20px; }'
            . 'h1, h2, p, pre { margin: 0; padding: 0; }'
            . 'body, pre { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif, "\5fae\8f6f\96c5\9ed1", "\5b8b\4f53"; }'
            . 'h1 { font-size: 36px; }'
            . 'h2 { font-size: 20px; margin: 20px 0 0; }'
            . 'pre { line-height: 18px; }'
            . 'strong, .error-text { color: #FF3000; }'
            . '</style>'
            . '</head>'
            . '<body>'
            . "<h1>$message</h1>"
            . $detail
            . '</body>'
            . '</html>';

        echo $html;
    }

    /**
     * The error handler convert PHP error to exception
     *
     * @param int $code The level of the error raised
     * @param string $message The error message
     * @param string $file The filename that the error was raised in
     * @param int $line The line number the error was raised at
     * @throws \ErrorException convert PHP error to exception
     * @internal use for set_error_handler only
     */
    public function handleError($code, $message, $file, $line)
    {
        if (!(error_reporting() & $code)) {
            // This error code is not included in error_reporting
            return;
        }
        restore_error_handler();
        throw new \ErrorException($message, $code, 500, $file, $line);
    }

    /**
     * Get file code in specified range
     *
     * @param  string $file  The file name
     * @param  int    $line  The file line
     * @param  int    $range The line range
     * @return string
     */
    public function getFileCode($file, $line, $range = 20)
    {
        $code = file($file);
        $half = (int) ($range / 2);

        $start = $line - $half;
        0 > $start && $start = 0;

        $total = count($code);
        $end = $line + $half;
        $total < $end && $end = $total;

        $len = strlen($end);

        array_unshift($code, null);
        $content = '';
        for ($i = $start; $i < $end; $i++) {
            $temp = str_pad($i, $len, 0, STR_PAD_LEFT) . ':  ' . $code[$i];
            if ($line != $i) {
                $content .= htmlspecialchars($temp, ENT_QUOTES);
            } else {
                $content .= '<strong>' . htmlspecialchars($temp, ENT_QUOTES) . '</strong>';
            }
        }

        return $content;
    }
}
