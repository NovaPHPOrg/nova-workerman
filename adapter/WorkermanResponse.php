<?php

namespace adapter;

use nova\framework\core\Logger;
use nova\framework\http\Response;

class WorkermanResponse extends Response
{

    function sendSSE(): void
    {
        $callback = $this->data;
        $this->sendHeaders();
        if ($this->isHead()) {
            return;
        }
        WorkermanApp::instance()->sendResponse();
        $callback(function ($data, $event = null) {
            if ($data == null) return;
            WorkermanApp::instance()->sendSSE(['data' => $data, 'event' => $event ?? 'message']);
        });
        while (!connection_aborted()) {
            sleep(1);
        }
    }

    protected function sendFile(): void
    {

    }
}