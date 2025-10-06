<?php

namespace EnedisLabBZH\Core;

define('LEVEL_NOTICE', 0);
define('LEVEL_WARNING', 1);
define('LEVEL_ERROR', 2);
define('LEVEL_FATAL', 3);

class CoreException extends \Exception
{
    public function __construct($message, $code = LEVEL_NOTICE, \Throwable $previous = null, $app = null)
    {
        parent::__construct($message, $code, $previous);
        error_log($this);

        isRuningOnDevServer()
            && $this->exposeMessageInHeaders($message);

        $app = $app ? $app : explode('.', getenv('HOSTNAME'))[0];
        isRuningOnProdServer()
            && LEVEL_FATAL === $code
            && sendTechnicalMail(
                'Erreur sur le serveur ' . $app
                    . ' à la ligne ' . $this->getLine()
                    . ' du fichier ' . $this->getFile(),
                $this->getMessage(),
                'bzh-incidents-appli@enedis.fr'
            );
        isRuningOnProdServer()
            && $code >= LEVEL_WARNING
            && sendTechnicalMail(
                'Erreur sur le serveur ' . $app
                    . ' à la ligne ' . $this->getLine()
                    . ' du fichier ' . $this->getFile(),
                $this->getMessage()
            );
    }

    private function exposeMessageInHeaders($message)
    {
        if (!@headers_sent() && (hasRole('DEVELOPER') || hasRole('DEVELOPPEUR'))) {
            !headers_sent() && @header('Access-Control-Expose-Headers: X-Message');
            !headers_sent() && @header('X-Message: ' . base64_encode($message));
            !headers_sent() && @header(TEMPORARY_HEADER_TRUE, true, 500);
            header_remove('Temporary-Header');
            http_response_code(500);
        }
    }
}
